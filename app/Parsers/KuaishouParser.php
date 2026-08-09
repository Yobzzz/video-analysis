<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;

/**
 * 快手视频解析器
 */
class KuaishouParser extends AbstractParser
{
    public static function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Referer' => 'https://www.kuaishou.com/',
        ];
    }

    public static function parse(string $url): array
    {
        // 获取重定向目标 URL
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_NOBODY => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        ] + (class_exists('App\Utils\HttpClient') ? \App\Utils\HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));
        curl_exec($ch);
        $target = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
        curl_close($ch);

        // 提取 photoId
        $photoId = '';
        if (preg_match('/photoId=(\w+)/i', $target, $m)) {
            $photoId = $m[1];
        } elseif (preg_match('#/(?:photo|short-video|fw/photo)/(\w+)#i', $target, $m)) {
            $photoId = $m[1];
        }

        if ($photoId === '') {
            throw new \InvalidArgumentException("无法识别快手视频 ID");
        }

        // GraphQL 请求
        $query = 'query visionPhotoDetail($photoId: String) { visionPhotoDetail(photoId: $photoId) { photo { id caption coverUrl duration likeCount viewCount photoUrl timestamp user { id name avatar } music { id name author coverUrl } } } }';
        $payload = json_encode([
            'operationName' => 'visionPhotoDetail',
            'query' => $query,
            'variables' => ['photoId' => $photoId],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init('https://www.kuaishou.com/graphql');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'Referer: https://www.kuaishou.com/',
            ],
        ] + (class_exists('App\Utils\HttpClient') ? \App\Utils\HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new \RuntimeException("快手 GraphQL 请求失败 (HTTP {$httpCode})");
        }

        $data = self::parseJson($response);
        if (!$data) {
            throw new \RuntimeException("快手 API 返回格式异常");
        }

        $photo = $data['data']['visionPhotoDetail']['photo'] ?? null;
        if (!$photo || empty($photo['photoUrl'])) {
            // 检查是否有captcha或登录要求
            if (isset($data['errors'])) {
                $msg = $data['errors'][0]['message'] ?? '未知错误';
                throw new \RuntimeException("快手API错误: {$msg}");
            }
            throw new \RuntimeException("未找到视频URL，可能需登录");
        }

        return [
            'author' => $photo['user']['name'] ?? '',
            'uid' => $photo['user']['id'] ?? '',
            'avatar' => $photo['user']['avatar'] ?? '',
            'like' => $photo['likeCount'] ?? 0,
            'time' => $photo['timestamp'] ?? 0,
            'title' => $photo['caption'] ?? '',
            'cover' => $photo['coverUrl'] ?? '',
            'url' => self::fixUrl($photo['photoUrl']),
            'music' => [
                'author' => $photo['music']['author'] ?? '',
                'avatar' => $photo['music']['coverUrl'] ?? '',
            ],
        ];
    }
}
