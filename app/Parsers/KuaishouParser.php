<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;

/**
 * 快手视频解析器 — 从移动端分享页面抓取
 */
class KuaishouParser extends AbstractParser
{
    public static function getHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
            'Referer' => 'https://www.kuaishou.com/',
        ];
    }

    public static function parse(string $url): array
    {
        // 跟随短链重定向
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_NOBODY => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
        ] + self::sslOptions());
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
            throw new \InvalidArgumentException('无法识别快手视频 ID');
        }

        // 抓取移动端页面
        $pageUrl = $target; // 使用重定向后的完整 URL（含 query 参数）
        $ch = curl_init($pageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
            CURLOPT_REFERER => 'https://www.kuaishou.com/',
        ] + self::sslOptions());
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$html || $httpCode >= 400) {
            throw new \RuntimeException('快手页面不可用 (HTTP ' . $httpCode . ')');
        }

        // 提取视频 URL — 优先高清版本
        $videoUrl = '';
        if (preg_match('#(https?://[^"\s<>]+(?:hd15|hd16|video-mz)[^"\s<>]*\.mp4[^"\s<>"]*)#i', $html, $m)) {
            $videoUrl = $m[1];
        } elseif (preg_match('#(https?://[^"\s<>]+\.mp4[^"\s<>"]*)#i', $html, $m)) {
            $videoUrl = $m[1];
        }
        if ($videoUrl === '') {
            throw new \RuntimeException('未找到视频 URL');
        }

        // 提取标题
        $caption = '';
        if (preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $html, $m)) {
            $caption = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/"caption"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $caption = $m[1];
        } elseif (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
            $caption = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
            if ($caption === '快手') $caption = '';
        }

        // 提取封面
        $coverUrl = '';
        if (preg_match('#("https?://[^"]+upic[^"]+\.(?:jpg|jpeg|webp)[^"]*")#i', $html, $m)) {
            $coverUrl = trim($m[1], '"');
        }

        // 提取作者名（优先找user.name,其次authorName）
        $authorName = '';
        if (preg_match('/"userName"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $authorName = $m[1];
        } elseif (preg_match('/"authorName"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $authorName = $m[1];
        } elseif (preg_match('/"nickName"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $authorName = $m[1];
        }

        // 提取头像
        $avatar = '';
        if (preg_match('#(https?://[^"]+/uhead/[^"]+_s\.jpg[^"]*)#i', $html, $m)) {
            $avatar = $m[1];
        }

        // 音乐信息
        $musicName = '';
        $musicAvatar = '';
        if (preg_match('/"author"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $musicName = $m[1];
        }
        if (preg_match('#(https?://[^"]+ost/[^"]+\.(?:jpg|png|m4a)[^"]*)#i', $html, $m)) {
            $musicAvatar = $m[1];
        }

        return [
            'author' => $authorName,
            'uid' => $photoId,
            'avatar' => $avatar,
            'like' => 0,
            'time' => 0,
            'title' => $caption,
            'cover' => $coverUrl,
            'url' => self::fixUrl($videoUrl),
            'music' => [
                'author' => $musicName,
                'avatar' => $musicAvatar,
            ],
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
