<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;

/**
 * 小红书解析器 — __INITIAL_STATE__ 提取（参考 qianxunbainian/jiexi）
 * 支持视频/图文/实况
 */
class XiaohongshuParser extends AbstractParser
{
    private const MOBILE_UA = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36';

    public static function getHeaders(): array
    {
        return ['User-Agent' => self::MOBILE_UA, 'Referer' => 'https://www.xiaohongshu.com/'];
    }

    public static function parse(string $url): array
    {
        // 预处理：xhs.com → xhslink.com
        $url = str_replace('xhs.com', 'xhslink.com', $url);

        $domain = parse_url($url, PHP_URL_HOST);
        if ($domain !== 'www.xiaohongshu.com') {
            $location = \App\Utils\HttpClient::getLocation($url);
            $url = $location ?: $url;
        }

        $id = self::extractId($url);
        if (!$id) throw new \InvalidArgumentException('无法识别小红书笔记 ID');

        // 抓取页面
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => self::MOBILE_UA,
            CURLOPT_REFERER => 'https://www.xiaohongshu.com/',
        ] + self::sslOptions());
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$html || $httpCode >= 400) {
            // 备用 UA 重试
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                CURLOPT_REFERER => 'https://www.xiaohongshu.com/',
            ] + self::sslOptions());
            $html = curl_exec($ch);
            curl_close($ch);
        }

        if (!$html) throw new \RuntimeException('小红书页面不可用');

        // 提取 __INITIAL_STATE__
        $data = self::extractFromInitialState($html, $id);
        if ($data !== null) return $data;

        // Token + API 降级
        $token = '';
        if (preg_match('/token=(.*?)&/', $html, $m)) $token = $m[1];
        elseif (preg_match('/"xsec_token":\s*"([^"]+)"/', $html, $m)) $token = $m[1];

        if ($token) {
            $apiUrl = "https://www.xiaohongshu.com/discovery/item/{$id}?app_platform=android&app_version=8.69.5&share_from_user_hidden=true&xsec_source=app_share&type=video&xsec_token={$token}";
            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => self::MOBILE_UA] + self::sslOptions());
            $apiHtml = curl_exec($ch); curl_close($ch);
            if ($apiHtml) $data = self::extractFromInitialState($apiHtml, $id);
        }

        if ($data !== null) return $data;
        throw new \RuntimeException('解析失败，未找到有效内容');
    }

    private static function extractFromInitialState(string $html, string $id): ?array
    {
        if (!preg_match('/<script>\s*window\.__INITIAL_STATE__\s*=\s*({[\s\S]*?})<\/script>/is', $html, $m)) return null;

        $jsonStr = str_replace('undefined', 'null', $m[1]);
        $json = json_decode($jsonStr, true);
        if (!$json) return null;

        $note = $json['note']['noteDetailMap'][$id]['note'] ?? $json['noteData']['data']['noteData'] ?? null;
        if (!$note) return null;

        $type = $note['type'] ?? '';
        if ($type === 'normal') $type = 'image';

        // 封面
        $cover = '';
        if (!empty($note['imageList'])) {
            $fi = $note['imageList'][0];
            $cover = $fi['urlPre'] ?? $fi['urlDefault'] ?? $fi['url'] ?? '';
        }

        // 视频 URL —— 优先取无水印原片 key（originVideoKey），
        // masterUrl 是小红书带水印的流式播放地址，只能作为兜底。
        $videoUrl = '';
        if ($type === 'video') {
            if (!empty($note['video']['consumer']['originVideoKey'])) {
                $videoUrl = 'https://sns-video-bd.xhscdn.com/' . $note['video']['consumer']['originVideoKey'];
            }
            if (!$videoUrl) {
                $streams = [];
                foreach (['h265', 'h264'] as $codec) {
                    foreach (($note['video']['media']['stream'][$codec] ?? []) as $s) {
                        $s['_codec'] = $codec;
                        $streams[] = $s;
                    }
                }
                if ($streams) {
                    usort($streams, fn($a, $b) => ($b['avgBitrate'] ?? 0) - ($a['avgBitrate'] ?? 0));
                    $videoUrl = $streams[0]['masterUrl'] ?? '';
                }
            }
        }

        return [
            'author' => $note['user']['nickname'] ?? $note['user']['nickName'] ?? '',
            'uid'    => (string)($note['user']['userId'] ?? $id),
            'avatar' => $note['user']['avatar'] ?? '',
            'like'   => $note['interactInfo']['likedCount'] ?? 0,
            'time'   => $note['time'] ?? 0,
            'title'  => $note['title'] ?? $note['desc'] ?? '',
            'cover'  => $cover,
            'url'    => $type === 'video' ? self::fixUrl($videoUrl) : '',
            'music'  => ['author' => '', 'avatar' => ''],
        ];
    }

    private static function extractId(string $url): ?string
    {
        foreach (['/discovery/item/(\w+)', '/explore/(\w+)', '/item/(\w+)', '/note/(\w+)'] as $p) {
            if (preg_match("#{$p}#i", $url, $m)) return $m[1];
        }
        return null;
    }

    private static function sslOptions(): array
    {
        if (class_exists('App\Utils\HttpClient')) return \App\Utils\HttpClient::getSslOptions();
        return [CURLOPT_SSL_VERIFYPEER => false];
    }
}
