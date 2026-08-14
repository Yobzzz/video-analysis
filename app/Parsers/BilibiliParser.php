<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;

/**
 * B站视频解析器 — 官方 API（参考 qianxunbainian/jiexi）
 */
class BilibiliParser extends AbstractParser
{
    public static function getHeaders(): array
    {
        return ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36'];
    }

    public static function parse(string $url): array
    {
        $bvid = self::extractBvid($url);
        if (!$bvid) throw new \InvalidArgumentException('无法识别 B 站视频 ID');

        // 获取视频信息
        $viewUrl = 'https://api.bilibili.com/x/web-interface/view?bvid=' . $bvid;
        $ch = curl_init($viewUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-type: application/json;charset=UTF-8'],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36',
        ] + self::sslOptions());
        $viewJson = curl_exec($ch); curl_close($ch);
        $viewData = json_decode($viewJson, true);

        if (($viewData['code'] ?? -1) !== 0) {
            throw new \RuntimeException('B站视频信息获取失败');
        }

        $data = $viewData['data'];
        $pages = $data['pages'] ?? [];
        if (!$pages) throw new \RuntimeException('未找到视频分P');

        $page = $pages[0];
        $cid = $page['cid'];

        // 获取播放地址 (qn=112 = 1080P高清)
        $playUrl = "https://api.bilibili.com/x/player/playurl?otype=json&fnver=0&fnval=3&player=3&qn=112&bvid={$bvid}&cid={$cid}&platform=html5&high_quality=1";
        $ch = curl_init($playUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-type: application/json;charset=UTF-8', 'Referer: https://www.bilibili.com/'],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ] + self::sslOptions());
        $playJson = curl_exec($ch); curl_close($ch);
        $playData = json_decode($playJson, true);

        $videoUrl = '';
        if (isset($playData['data']['durl'][0]['url'])) {
            $rawUrl = $playData['data']['durl'][0]['url'];
            $parts = explode('.bilivideo.com/', $rawUrl);
            $videoUrl = count($parts) > 1 ? 'https://upos-sz-mirrorhw.bilivideo.com/' . $parts[1] : $rawUrl;
        }
        if (!$videoUrl) throw new \RuntimeException('未获取到视频播放地址');

        return [
            'author' => $data['owner']['name'] ?? '',
            'uid'    => (string)($data['owner']['mid'] ?? ''),
            'avatar' => $data['owner']['face'] ?? '',
            'like'   => $data['stat']['like'] ?? 0,
            'time'   => $data['pubdate'] ?? 0,
            'title'  => $data['title'] ?? '',
            'cover'  => $data['pic'] ?? '',
            'url'    => self::fixUrl($videoUrl),
            'music'  => ['author' => '', 'avatar' => ''],
        ];
    }

    private static function extractBvid(string $url): ?string
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';

        if ($host === 'b23.tv') {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'Mozilla/5.0'] + self::sslOptions());
            curl_exec($ch);
            $realUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); curl_close($ch);
            if ($realUrl && $realUrl !== $url) {
                if (preg_match('/\/video\/(BV\w+)/i', $realUrl, $m)) return $m[1];
            }
            return null;
        }

        if (preg_match('/(BV\w+)/i', $url, $m)) return $m[1];
        return null;
    }

    private static function sslOptions(): array
    {
        if (class_exists('App\Utils\HttpClient')) return \App\Utils\HttpClient::getSslOptions();
        return [CURLOPT_SSL_VERIFYPEER => false];
    }
}
