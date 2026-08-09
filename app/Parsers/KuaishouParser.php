<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;

/**
 * 快手视频解析器 — 从移动端分享页面抓取视频数据
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
            CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
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

        // 从移动端页面抓取数据
        $pageUrl = $target; // 使用重定向后的完整URL（含query参数）
        $ch = curl_init($pageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1',
            CURLOPT_REFERER => 'https://www.kuaishou.com/',
        ] + (class_exists('App\Utils\HttpClient') ? \App\Utils\HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$html || $httpCode >= 400) {
            throw new \RuntimeException("快手页面请求失败 (HTTP {$httpCode})");
        }

        // 从 HTML 提取视频 URL（优先高清版 hd15/hd16）
        $videoUrl = '';
        $coverUrl = '';
        $caption = '';
        $authorName = '';
        $avatar = '';
        $musicName = '';
        $musicAvatar = '';
        $likeCount = 0;
        $timestamp = 0;
        $authorId = '';

        // 视频 URL: hd15/hd16 高清版本优先
        if (preg_match('#https?://[^"\s<>]+(?:hd1[56]|video)[^"\s<>]*\.mp4[^"\s<>"]*#i', $html, $m)) {
            $videoUrl = $m[0];
        } elseif (preg_match('#https?://[^"\s<>]+\.mp4[^"\s<>"]*#i', $html, $m)) {
            $videoUrl = $m[0];
        }

        // 封面
        if (preg_match('#\"(https?://[^\"]+\.(?:jpg|jpeg|png|webp)[^\"]*clientCacheKey=' . preg_quote($photoId, '#') . '\.jpg[^\"]*)#i', $html, $m)) {
            $coverUrl = $m[1];
        } elseif (preg_match('#(https?://[^\"]+upic[^\"]+\.jpg[^\"]*)#i', $html, $m)) {
            $coverUrl = $m[1];
        }

        // 标题
        if (preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $html, $m)) {
            $caption = $m[1];
        }

        // 作者名、ID、头像 — 尝试从 JSON 提取
        if (preg_match('/"name"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $candidates = [];
            preg_match_all('/"name"\s*:\s*"([^"]+)"/i', $html, $candidates);
            foreach ($candidates[1] as $name) {
                if (mb_strlen($name) > 1 && mb_strlen($name) < 30) {
                    if (stripos($name, '游戏') === false || mb_strlen($name) < 10) {
                        $authorName = $name;
                        break;
                    }
                }
            }
        }

        // 头像
        if (preg_match('#(https?://[^\"]+/uhead/[^\"]+_s\.jpg[^\"]*)#i', $html, $m)) {
            $avatar = $m[1];
        }

        // 音乐名
        if (preg_match('/"author"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $musicName = $m[1];
        }
        // 音乐封面
        if (preg_match('#(https?://[^\"]+ost/[^\"]+\.(?:jpg|png)[^\"]*)#i', $html, $m)) {
            $musicAvatar = $m[1];
        }

        if ($videoUrl === '') {
            throw new \RuntimeException("未找到视频URL");
        }

        return [
            'author' => $authorName,
            'uid' => $photoId,
            'avatar' => $avatar,
            'like' => $likeCount,
            'time' => $timestamp,
            'title' => $caption ?: '',
            'cover' => $coverUrl,
            'url' => self::fixUrl($videoUrl),
            'music' => [
                'author' => $musicName,
                'avatar' => $musicAvatar,
            ],
        ];
    }
}
