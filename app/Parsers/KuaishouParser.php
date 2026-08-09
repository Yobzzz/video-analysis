<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;

/**
 * 快手视频解析器 — INIT_STATE 提取（参考 qianxunbainian/jiexi）
 */
class KuaishouParser extends AbstractParser
{
    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1';

    public static function getHeaders(): array
    {
        return [
            'User-Agent' => self::MOBILE_UA,
            'Referer' => 'https://www.kuaishou.com/',
        ];
    }

    public static function parse(string $url): array
    {
        // 1. 跟随重定向
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_NOBODY => true,
            CURLOPT_USERAGENT => self::MOBILE_UA,
        ] + self::sslOptions());
        curl_exec($ch);
        $redirect = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
        curl_close($ch);

        // 2. 获取页面内容
        $ch = curl_init($redirect);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => self::MOBILE_UA,
            CURLOPT_REFERER => 'https://www.kuaishou.com/',
        ] + self::sslOptions());
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$html || $httpCode >= 400) {
            throw new \RuntimeException('快手页面不可用 (HTTP ' . $httpCode . ')');
        }

        // 3. 提取 INIT_STATE（含完整视频数据）
        $data = self::parseInitState($html, $redirect);
        if ($data !== null) {
            return $data;
        }

        // 4. 降级：从 HTML 提取
        return self::parseFromHtml($html);
    }

    /**
     * 从 window.INIT_STATE 提取完整数据
     */
    private static function parseInitState(string $html, string $url): ?array
    {
        if (!preg_match('/window\.INIT_STATE\s*=\s*(.*?)<\/script>/s', $html, $m)) {
            return null;
        }

        $json = rtrim(trim($m[1]), ';');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // 容错清理
            $json = stripslashes($json);
            $json = str_replace(['"{"err_msg":"launchApplication:fail"}"', '"{"err_msg":"system:access_denied"}"'], ['err_msg','err_msg'], $json);
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) return null;
        }

        // 遍历提取第一个有效媒体对象
        $photo = null;
        $stack = [$data];
        while (!empty($stack)) {
            $node = array_shift($stack);
            if (!is_array($node)) continue;
            if (isset($node['photo']) && is_array($node['photo']) && isset($node['photo']['photoUrl'])) {
                $photo = $node['photo'];
                break;
            }
            foreach ($node as $v) {
                if (is_array($v)) $stack[] = $v;
            }
        }

        if (!$photo) return null;

        $videoUrl = $photo['photoUrl'] ?? '';
        if (!$videoUrl) return null;

        // 提取 photoId
        $photoId = '';
        if (preg_match('/photoId=(\w+)/i', $url, $mm)) $photoId = $mm[1];
        elseif (preg_match('#/(\w+)(?:\?|$)#', $url, $mm)) $photoId = $mm[1];

        // 音乐
        $music = $photo['music'] ?? $photo['soundTrack'] ?? [];
        $musicData = [
            'author' => $music['artist'] ?? $music['author'] ?? $music['name'] ?? '',
            'avatar' => $music['imageUrls'][0]['url'] ?? $music['avatarUrls'][0]['url'] ?? $music['coverUrls'][0]['url'] ?? '',
        ];

        return [
            'author' => $photo['userName'] ?? '',
            'uid'    => $photoId,
            'avatar' => $photo['headUrl'] ?? '',
            'like'   => $photo['likeCount'] ?? 0,
            'time'   => $photo['timestamp'] ?? 0,
            'title'  => $photo['caption'] ?? '',
            'cover'  => $photo['coverUrls'][0]['url'] ?? $photo['coverUrl'] ?? '',
            'url'    => self::fixUrl($videoUrl),
            'music'  => $musicData,
        ];
    }

    /**
     * HTML 降级解析
     */
    private static function parseFromHtml(string $html): array
    {
        $videoUrl = '';
        if (preg_match('#(https?://[^"\s<>]+(?:hd1[56]|photo-video-mz)[^"\s<>]*\.mp4[^"\s<>",);]*)#i', $html, $m)) {
            $videoUrl = $m[1];
        } elseif (preg_match('#(https?://[^"\s<>]+\.mp4[^"\s<>",);]*)#i', $html, $m)) {
            $videoUrl = $m[1];
        }
        if (!$videoUrl) {
            throw new \RuntimeException('未找到视频 URL');
        }

        $caption = '';
        if (preg_match('/"caption"\s*:\s*"([^"]+)"/i', $html, $m)) $caption = $m[1];

        $author = '';
        if (preg_match('/"userName"\s*:\s*"([^"]+)"/i', $html, $m)) $author = $m[1];

        $cover = '';
        if (preg_match('#(https?://[^"\s<>]+upic[^"\s<>]+\.(?:jpg|jpeg|webp)[^"\s<>",);]*)#i', $html, $m)) $cover = $m[1];

        $avatar = '';
        if (preg_match('#(https?://[^"\s<>]+uhead[^"\s<>]+_s\.jpg[^"\s<>)",;]*)#i', $html, $m)) $avatar = $m[1];

        $musicName = '';
        if (preg_match('/"name"\s*:\s*"([^"]+)"/i', $html, $m)) $musicName = $m[1];
        $musicCover = '';
        if (preg_match('#(https?://[^"\s<>]+ost/[^"\s<>]+\.(?:jpg|png)[^"\s<>",);]*)#i', $html, $m)) $musicCover = $m[1];

        return [
            'author' => $author,
            'uid'    => '',
            'avatar' => $avatar,
            'like'   => 0,
            'time'   => 0,
            'title'  => $caption,
            'cover'  => $cover,
            'url'    => self::fixUrl($videoUrl),
            'music'  => ['author' => $musicName, 'avatar' => $musicCover],
        ];
    }

    private static function sslOptions(): array
    {
        if (class_exists('App\Utils\HttpClient')) {
            return \App\Utils\HttpClient::getSslOptions();
        }
        return [CURLOPT_SSL_VERIFYPEER => false];
    }
}
