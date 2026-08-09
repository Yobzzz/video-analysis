<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;
use App\Utils\HttpClient;
use App\Utils\UserAgent;

/**
 * 快手视频解析器 — 支持短链重定向 + GraphQL API
 */
class KuaishouParser extends AbstractParser
{
    private const GRAPHQL_URL = 'https://www.kuaishou.com/graphql';
    private const COOKIE_URL  = 'https://www.kuaishou.com/';

    public static function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Referer' => 'https://www.kuaishou.com/',
            'Accept-Language' => 'zh-CN,zh;q=0.9',
        ];
    }

    public static function parse(string $url): array
    {
        // Step 1: Get cookies by visiting kuaishou.com first
        $cookieJar = self::getCookies();

        // Step 2: Follow redirect of short link
        $location = HttpClient::getLocation($url);
        $target = $location ?: $url;

        // Step 3: Extract photoId from URL
        $photoId = '';
        if (preg_match('/photoId=(\w+)/i', $target, $m)) {
            $photoId = $m[1];
        } elseif (preg_match('#/(?:photo|short-video|fw/photo)/(\w+)#i', $target, $m)) {
            $photoId = $m[1];
        }

        if ($photoId === '') {
            throw new \InvalidArgumentException("无法解析快手视频 ID，请检查链接");
        }

        // Step 4: Call GraphQL with cookies
        $query = 'query visionPhotoDetail($photoId: String) { visionPhotoDetail(photoId: $photoId) { photo { id caption coverUrl duration likeCount viewCount photoUrl timestamp user { id name avatar } music { id name author coverUrl } } } }';

        $result = self::graphqlRequest([
            'operationName' => 'visionPhotoDetail',
            'query' => $query,
            'variables' => ['photoId' => $photoId],
        ], $cookieJar);

        if (!$result) {
            throw new \RuntimeException("解析视频信息失败，请稍后重试");
        }

        $data = self::parseJson($result);
        $photo = $data['data']['visionPhotoDetail']['photo'] ?? null;
        if (!$photo) {
            throw new \RuntimeException("视频数据解析失败");
        }

        $videoUrl = $photo['photoUrl'] ?? '';
        if (!$videoUrl) {
            throw new \RuntimeException("未找到视频 URL");
        }

        return [
            'author' => $photo['user']['name'] ?? '',
            'uid' => $photo['user']['id'] ?? '',
            'avatar' => $photo['user']['avatar'] ?? '',
            'like' => $photo['likeCount'] ?? 0,
            'time' => $photo['timestamp'] ?? 0,
            'title' => $photo['caption'] ?? '',
            'cover' => $photo['coverUrl'] ?? '',
            'url' => self::fixUrl($videoUrl),
            'music' => [
                'author' => $photo['music']['author'] ?? '',
                'avatar' => $photo['music']['coverUrl'] ?? '',
            ],
        ];
    }

    /**
     * 从 kuaishou.com 获取初始 Cookie
     */
    private static function getCookies(): string
    {
        $ch = curl_init(self::COOKIE_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        ] + (class_exists('App\Utils\HttpClient') ? \App\Utils\HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));

        curl_exec($ch);
        $cookies = [];
        // Extract Set-Cookie from response
        $headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        curl_close($ch);

        // Re-fetch with cookie support
        $ch = curl_init(self::COOKIE_URL);
        $cookieFile = tempnam(sys_get_temp_dir(), 'ks_cookie_');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        ] + (class_exists('App\Utils\HttpClient') ? \App\Utils\HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));
        curl_exec($ch);
        return $cookieFile;
    }

    /**
     * 调用 GraphQL API (带 Cookie)
     */
    private static function graphqlRequest(array $payload, string $cookieJar): string|false
    {
        $ch = curl_init(self::GRAPHQL_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_COOKIEFILE => $cookieJar,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'Referer: https://www.kuaishou.com/',
            ],
        ] + (class_exists('App\Utils\HttpClient') ? \App\Utils\HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($cookieJar);

        return ($httpCode === 200 && $response !== false) ? $response : false;
    }
}
