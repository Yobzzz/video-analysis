<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;
use App\Utils\HttpClient;
use App\Utils\UserAgent;

/**
 * 快手视频解析器 — GraphQL + INIT_STATE 降级
 */
class KuaishouParser extends AbstractParser
{
    private const MOBILE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1';

    public static function getHeaders(): array
    {
        return [
            "User-Agent" => UserAgent::mobile(),
            "Referer" => "https://www.kuaishou.com/",
        ];
    }

    public static function parse(string $url): array
    {
        $photoId = self::extractPhotoId($url);
        if ($photoId === '') {
            throw new \InvalidArgumentException("无法解析快手视频 ID");
        }

        // 策略1: GraphQL API (like reference)
        $result = self::fetch("https://www.kuaishou.com/graphql", json_encode([
            "operationName" => "visionPhotoDetail",
            "query" => "query visionPhotoDetail(\$photoId: String) { visionPhotoDetail(photoId: \$photoId) { photo { id caption coverUrl duration likeCount viewCount photoUrl timestamp user { id name avatar } music { id name author coverUrl } } } }",
            "variables" => ["photoId" => $photoId],
        ], JSON_UNESCAPED_SLASHES));

        if ($result) {
            $data = self::parseJson($result["data"]);
            $photo = $data["data"]["visionPhotoDetail"]["photo"] ?? null;
            if ($photo && !empty($photo["photoUrl"])) {
                return [
                    "author" => $photo["user"]["name"] ?? "",
                    "uid" => $photo["user"]["id"] ?? "",
                    "avatar" => $photo["user"]["avatar"] ?? "",
                    "like" => $photo["likeCount"] ?? 0,
                    "time" => $photo["timestamp"] ?? 0,
                    "title" => $photo["caption"] ?? "",
                    "cover" => $photo["coverUrl"] ?? "",
                    "url" => self::fixUrl($photo["photoUrl"]),
                    "music" => [
                        "author" => $photo["music"]["author"] ?? "",
                        "avatar" => $photo["music"]["coverUrl"] ?? "",
                    ],
                ];
            }
        }

        // 策略2: INIT_STATE HTML 降级
        $html = self::fetchPage($url);
        if ($html) {
            return self::parseFromHtml($html, $photoId);
        }

        throw new \RuntimeException("解析视频信息失败");
    }

    private static function extractPhotoId(string $url): string
    {
        $location = HttpClient::getLocation($url);
        $target = $location ?: $url;

        if (preg_match('/(?:photo|short-video)\/(\w+)/i', $target, $match)) {
            return $match[1];
        }
        if (preg_match('#/(?:v|video)/(\w+)#i', $target, $m2)) {
            return $m2[1];
        }
        if (preg_match('/photoId=(\w+)/i', $target, $m)) {
            return $m[1];
        }
        return '';
    }

    private static function fetchPage(string $url): string|false
    {
        $ch = curl_init($url);
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
        return ($httpCode === 200 && $html) ? $html : false;
    }

    private static function parseFromHtml(string $html, string $photoId): array
    {
        // INIT_STATE
        $photo = null;
        if (preg_match('/window\.INIT_STATE\s*=\s*(.*?)<\/script>/s', $html, $m)) {
            $js = rtrim(trim($m[1]), ';');
            $d = json_decode($js, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $js = stripslashes($js);
                $d = json_decode($js, true);
            }
            if ($d) {
                $stack = [$d];
                while (!empty($stack)) {
                    $node = array_shift($stack);
                    if (!is_array($node)) continue;
                    if (isset($node['photo']) && is_array($node['photo']) && !empty($node['photo']['photoUrl'])) {
                        $photo = $node['photo'];
                        break;
                    }
                    foreach ($node as $v) if (is_array($v)) $stack[] = $v;
                }
            }
        }

        if ($photo) {
            $music = $photo['music'] ?? $photo['soundTrack'] ?? [];
            return [
                "author" => $photo['userName'] ?? "",
                "uid" => $photoId,
                "avatar" => $photo['headUrl'] ?? "",
                "like" => $photo['likeCount'] ?? 0,
                "time" => $photo['timestamp'] ?? 0,
                "title" => $photo['caption'] ?? "",
                "cover" => $photo['coverUrls'][0]['url'] ?? "",
                "url" => self::fixUrl($photo['photoUrl']),
                "music" => [
                    "author" => $music['artist'] ?? $music['author'] ?? $music['name'] ?? "",
                    "avatar" => $music['imageUrls'][0]['url'] ?? $music['avatarUrls'][0]['url'] ?? "",
                ],
            ];
        }

        // HTML fallback
        $videoUrl = '';
        if (preg_match('#(https?://[^"\s<>]+(?:hd1[56]|photo-video-mz)[^"\s<>]*\.mp4[^"\s<>",);]*)#i', $html, $m)) {
            $videoUrl = $m[1];
        } elseif (preg_match('#(https?://[^"\s<>]+\.mp4[^"\s<>",);]*)#i', $html, $m)) {
            $videoUrl = $m[1];
        }
        if (!$videoUrl) throw new \RuntimeException("未找到视频 URL");

        $caption = ''; $author = ''; $cover = ''; $avatar = ''; $musicName = ''; $musicCover = '';
        if (preg_match('/"caption"\s*:\s*"([^"]+)"/i', $html, $m)) $caption = $m[1];
        if (preg_match('/"userName"\s*:\s*"([^"]+)"/i', $html, $m)) $author = $m[1];
        if (preg_match('#(https?://[^"\s<>]+upic[^"\s<>]+\.(?:jpg|jpeg|webp)[^"\s<>",);]*)#i', $html, $m)) $cover = $m[1];
        if (preg_match('#(https?://[^"\s<>]+uhead[^"\s<>]+_s\.jpg[^"\s<>)",;]*)#i', $html, $m)) $avatar = $m[1];
        if (preg_match('/"name"\s*:\s*"([^"]+)"/i', $html, $m)) $musicName = $m[1];
        if (preg_match('#(https?://[^"\s<>]+ost/[^"\s<>]+\.(?:jpg|png)[^"\s<>",);]*)#i', $html, $m)) $musicCover = $m[1];

        return [
            "author" => $author, "uid" => $photoId, "avatar" => $avatar,
            "like" => 0, "time" => 0, "title" => $caption, "cover" => $cover,
            "url" => self::fixUrl($videoUrl),
            "music" => ["author" => $musicName, "avatar" => $musicCover],
        ];
    }

    private static function sslOptions(): array
    {
        if (class_exists('App\Utils\HttpClient')) return \App\Utils\HttpClient::getSslOptions();
        return [CURLOPT_SSL_VERIFYPEER => false];
    }
}
