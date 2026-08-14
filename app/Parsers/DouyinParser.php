<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;
use App\Utils\HttpClient;

/**
 * 抖音视频解析器 — 多策略解析（_ROUTER_DATA + API 降级）
 */
class DouyinParser extends AbstractParser
{
    private const SHARE_URL = 'https://www.iesdouyin.com/share/video/';
    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1';

    public static function getHeaders(): array
    {
        return ['User-Agent' => self::MOBILE_UA];
    }

    public static function parse(string $url): array
    {
        $videoId = self::extractVideoId($url);
        if ($videoId === null) {
            throw new \InvalidArgumentException('无法解析视频 ID');
        }

        // 策略1: 分享页面 _ROUTER_DATA（最可靠）
        $item = self::parseFromSharePage($videoId);
        if ($item !== null) {
            return self::buildOutput($item);
        }

        // 策略2: 原有 API 降级
        try {
            $item = self::fetchAwemeDetail($videoId);
        } catch (\RuntimeException $e) {
            $item = self::fetchLegacy($videoId);
        }

        if ($item === null) {
            throw new \RuntimeException('解析视频信息失败，视频可能已删除或接口暂时失效');
        }

        return self::buildOutput($item);
    }

    /**
     * 从分享页面 _ROUTER_DATA 提取数据（参考 qianxunbainian/jiexi）
     */
    private static function parseFromSharePage(string $videoId): ?array
    {
        $url = self::SHARE_URL . $videoId;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => self::MOBILE_UA,
        ] + self::sslOptions());
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$html) return null;

        if (!preg_match('/window\._ROUTER_DATA\s*=\s*(.*?)<\/script>/s', $html, $m)) return null;

        $raw = json_decode(trim($m[1]), true);
        if (!$raw) return null;

        $page = $raw['loaderData']['video_(id)/page']['videoInfoRes']['item_list'][0] ?? null;
        if (!$page) return null;

        return [
            'author'    => $page['author']['nickname'] ?? '',
            'uid'       => $page['author']['unique_id'] ?? $videoId,
            'avatar'    => $page['author']['avatar_medium']['url_list'][0] ?? '',
            'like'      => $page['statistics']['digg_count'] ?? 0,
            'time'      => $page['create_time'] ?? 0,
            'title'     => $page['desc'] ?? '',
            'cover'     => $page['video']['cover']['url_list'][0] ?? '',
            'video_url' => self::resolveCdnUrl(str_replace('playwm', 'play', $page['video']['play_addr']['url_list'][0] ?? '')),
            'duration'  => $page['video']['duration'] ?? 0,
            'music'     => [
                'title'  => $page['music']['title'] ?? '',
                'author' => $page['music']['author'] ?? '',
                'cover'  => $page['music']['cover_large']['url_list'][0] ?? '',
                'url'    => $page['music']['play_url']['uri'] ?? '',
            ],
        ];
    }

    /**
     * 策略2: 抖音 Web API（aweme/detail，需 ttwid Cookie + a_bogus 签名）
     * 签名由 scripts/a_bogus_server.js 提供（node 常驻服务，端口 A_BOGUS_PORT）
     */
    private static function fetchAwemeDetail(string $videoId): ?array
    {
        $config  = self::getConfig();
        $ua      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36';
        // 优先使用用户配置的浏览器 Cookie（可显著提高通过风控的概率）
        $cookie  = $config['cookie'] ?? '';
        if ($cookie === '') {
            $cookie = self::fetchTtwid();
        }
        $query   = http_build_query([
            'aweme_id'        => $videoId,
            'aid'             => '6383',
            'device_platform' => 'webapp',
            'channel'         => 'channel_pc_web',
        ]);
        $aBogus  = self::signQuery($query, $ua);
        $apiUrl  = ($config['detail_url'] ?? 'https://www.douyin.com/aweme/v1/web/aweme/detail/')
                 . '?' . $query . '&a_bogus=' . urlencode($aBogus);

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_REFERER        => 'https://www.douyin.com/',
            CURLOPT_COOKIE         => $cookie,
        ] + self::sslOptions());
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$body) return null;
        $json = json_decode($body, true);
        $d = $json['aweme_detail'] ?? null;
        if (!$d) return null;

        $video = $d['video'] ?? [];
        return [
            'author'    => $d['author']['nickname'] ?? '',
            'uid'       => $d['author']['unique_id'] ?? $videoId,
            'avatar'    => $d['author']['avatar_medium']['url_list'][0] ?? '',
            'like'      => $d['statistics']['digg_count'] ?? 0,
            'time'      => $d['create_time'] ?? 0,
            'title'     => $d['desc'] ?? '',
            'cover'     => $video['cover']['url_list'][0] ?? '',
            'video_url' => self::resolveCdnUrl(str_replace('playwm', 'play', $video['play_addr']['url_list'][0] ?? '')),
            'duration'  => $video['duration'] ?? 0,
            'music'     => [
                'title'  => $d['music']['title'] ?? '',
                'author' => $d['music']['author'] ?? '',
                'cover'  => $d['music']['cover_large']['url_list'][0] ?? '',
                'url'    => $d['music']['play_url']['uri'] ?? '',
            ],
        ];
    }

    /**
     * 策略3: iesdouyin 旧版 iteminfo API（无需签名，接口可能失效，仅作兜底）
     */
    private static function fetchLegacy(string $videoId): ?array
    {
        $config = self::getConfig();
        $url = ($config['legacy_url'] ?? 'https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/')
             . '?item_ids=' . $videoId;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => self::MOBILE_UA,
            CURLOPT_REFERER        => 'https://www.iesdouyin.com/',
        ] + self::sslOptions());
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$body) return null;
        $json = json_decode($body, true);
        $d = $json['item_list'][0] ?? null;
        if (!$d) return null;

        $video = $d['video'] ?? [];
        return [
            'author'    => $d['author']['nickname'] ?? '',
            'uid'       => $d['author']['unique_id'] ?? $videoId,
            'avatar'    => $d['author']['avatar_medium']['url_list'][0] ?? '',
            'like'      => $d['statistics']['digg_count'] ?? 0,
            'time'      => $d['create_time'] ?? 0,
            'title'     => $d['desc'] ?? '',
            'cover'     => $video['cover']['url_list'][0] ?? '',
            'video_url' => self::resolveCdnUrl(str_replace('playwm', 'play', $video['play_addr']['url_list'][0] ?? '')),
            'duration'  => $video['duration'] ?? 0,
            'music'     => [
                'title'  => $d['music']['title'] ?? '',
                'author' => $d['music']['author'] ?? '',
                'cover'  => $d['music']['cover_large']['url_list'][0] ?? '',
                'url'    => $d['music']['play_url']['uri'] ?? '',
            ],
        ];
    }

    /**
     * 获取 ttwid Cookie（从抖音任意页面响应头 Set-Cookie 提取）
     * 注：ttwid.bytedance.com 注册接口已失效（404 / parse params fail）
     */
    private static function fetchTtwid(): string
    {
        $ch = curl_init('https://live.douyin.com/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
        ] + self::sslOptions());
        $header = curl_exec($ch);
        curl_close($ch);

        if (!is_string($header)) return '';
        if (preg_match('/set-cookie:\s*ttwid=([^;]+)/i', $header, $m)) {
            return 'ttwid=' . trim($m[1]);
        }
        return '';
    }

    /**
     * 通过本地 a_bogus 签名服务为查询串生成签名
     * 服务未启动时返回空串（请求将不带签名，可能被风控）
     */
    private static function signQuery(string $query, string $ua): string
    {
        $config = self::getConfig();
        $port = (int)($config['a_bogus_port'] ?? 9876);
        $ch = curl_init('http://127.0.0.1:' . $port . '/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['query' => $query, 'ua' => $ua]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $sig = curl_exec($ch);
        curl_close($ch);
        return is_string($sig) ? trim($sig) : '';
    }

    /**
     * 提取视频 ID
     */
    protected static function extractVideoId(string $url): ?string
    {
        if (preg_match('#/(?:video|note|share/video)/(\d+)#i', $url, $match)) {
            return $match[1];
        }

        if (preg_match('#https?://v\.douyin\.com/#i', $url)) {
            $location = HttpClient::getLocation($url);
            if ($location && preg_match('#/(?:video|note|share/video)/(\d+)#i', $location, $match)) {
                return $match[1];
            }
        }

        if (preg_match('/(\d{17,20})/', $url, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * 构造返回结果
     */
    private static function buildOutput(array $data): array
    {
        $url = $data['video_url'] ?? $data['play_addr']['url_list'][0] ?? '';
        return [
            'author' => $data['author'] ?? '',
            'uid'    => $data['uid'] ?? '',
            'avatar' => $data['avatar'] ?? '',
            'like'   => (int)($data['like'] ?? $data['statistics']['digg_count'] ?? 0),
            'time'   => (int)($data['time'] ?? $data['create_time'] ?? 0),
            'title'  => $data['title'] ?? $data['desc'] ?? '',
            'cover'  => $data['cover'] ?? $data['video']['cover']['url_list'][0] ?? '',
            'url'    => self::fixUrl(self::resolveCdnUrl($url)),
            'music'  => [
                'author' => $data['music']['author'] ?? '',
                'avatar' => $data['music']['cover'] ?? $data['music']['cover_large']['url_list'][0] ?? '',
            ],
        ];
    }

    /**
     * 将播放重定向URL解析为直接CDN URL
     */
    private static function resolveCdnUrl(string $url): string
    {
        if (!$url || !preg_match('#/play/\?|aweme/v1/play#', $url)) return $url;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ] + self::sslOptions());
        curl_exec($ch);
        $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return ($final && $final !== $url) ? $final : $url;
    }

    private static function sslOptions(): array
    {
        if (class_exists('App\Utils\HttpClient')) {
            return \App\Utils\HttpClient::getSslOptions();
        }
        return [CURLOPT_SSL_VERIFYPEER => false];
    }
}
