<?php

declare(strict_types=1);

namespace App\Parsers;

use App\Contracts\AbstractParser;
use App\Utils\HttpClient;
use App\Utils\UserAgent;

/**
 * 快手视频解析器
 */
class KuaishouParser extends AbstractParser
{
    public static function getHeaders(): array
    {
        return [
            "User-Agent" => UserAgent::mobile(),
            "Referer" => "https://www.kuaishou.com/",
        ];
    }

    public static function parse(string $url): array
    {
        // 解析短链接或完整链接获取 photoId
        $location = HttpClient::getLocation($url);
        $target = $location ?: $url;

        // 快手分享短链接可能是 /photo/xxx 或 /v/xxx 格式，重定向后才有完整 photoId
        $photoId = '';
        if (preg_match('/(?:photo|short-video)\/(\w+)/i', $target, $match)) {
            $photoId = $match[1];
        } elseif (preg_match('#/(?:v|video)/(\w+)#i', $target, $m2)) {
            $photoId = $m2[1];
        }

        if ($photoId === '') {
            throw new \InvalidArgumentException("无法解析快手视频 ID");
        }

        $result = self::fetch("https://www.kuaishou.com/graphql", json_encode([
            "operationName" => "visionPhotoDetail",
            "query" => "query visionPhotoDetail(\$photoId: String) {\n  visionPhotoDetail(photoId: \$photoId) {\n    photo {\n      id\n      caption\n      coverUrl\n      duration\n      likeCount\n      viewCount\n      photoUrl\n      timestamp\n      user {\n        id\n        name\n        avatar\n      }\n      music {\n        id\n        name\n        author\n        coverUrl\n      }\n    }\n  }\n}",
            "variables" => ["photoId" => $photoId],
        ], JSON_UNESCAPED_SLASHES));

        if (!$result) {
            // Fallback: try page HTML extraction
            $htmlResult = self::fetch($target);
            if ($htmlResult && $htmlResult['data']) {
                $html = $htmlResult['data'];
                if (preg_match('/"photoUrl"\s*:\s*"([^"]+)"/i', $html, $m)) {
                    return self::parseFromHtml($html, $photoId);
                }
            }
            throw new \RuntimeException("解析视频信息失败，请检查链接是否有效");
        }

        $data = self::parseJson($result["data"]);
        $photo = $data["data"]["visionPhotoDetail"]["photo"] ?? null;
        if (!$photo) {
            throw new \RuntimeException("视频数据解析失败");
        }

        $videoUrl = $photo["photoUrl"] ?? "";
        if (!$videoUrl) {
            throw new \RuntimeException("未找到视频 URL");
        }

        return [
            "author" => $photo["user"]["name"] ?? "",
            "uid" => $photo["user"]["id"] ?? "",
            "avatar" => $photo["user"]["avatar"] ?? "",
            "like" => $photo["likeCount"] ?? 0,
            "time" => $photo["timestamp"] ?? 0,
            "title" => $photo["caption"] ?? "",
            "cover" => $photo["coverUrl"] ?? "",
            "url" => self::fixUrl($videoUrl),
            "music" => [
                "author" => $photo["music"]["author"] ?? "",
                "avatar" => $photo["music"]["coverUrl"] ?? "",
            ],
        ];
    }

    private static function parseFromHtml(string $html, string $photoId): array
    {
        $data = [];
        if (preg_match('/"photoUrl"\s*:\s*"([^"]+)"/i', $html, $m)) $data['url'] = self::fixUrl($m[1]);
        if (preg_match('/"caption"\s*:\s*"([^"]*)"/i', $html, $m)) $data['title'] = $m[1];
        if (preg_match('/"coverUrl"\s*:\s*"([^"]+)"/i', $html, $m)) $data['cover'] = $m[1];
        if (preg_match('/"likeCount"\s*:\s*(\d+)/i', $html, $m)) $data['like'] = (int)$m[1];
        if (preg_match('/"name"\s*:\s*"([^"]+)"/i', $html, $m)) $data['author'] = $m[1];
        if (preg_match('/"timestamp"\s*:\s*(\d+)/i', $html, $m)) $data['time'] = (int)$m[1];
        if (preg_match('/"avatar"\s*:\s*"([^"]+)"/i', $html, $m)) $data['avatar'] = $m[1];
        if (preg_match('/"author"\s*:\s*"([^"]+)"/i', $html, $m) && !isset($data['music'])) $data['music'] = ['author' => $m[1]];
        
        if (!isset($data['url'])) throw new \RuntimeException('未找到视频URL');
        
        return [
            "author" => $data['author'] ?? '',
            "uid" => $photoId,
            "avatar" => $data['avatar'] ?? '',
            "like" => $data['like'] ?? 0,
            "time" => $data['time'] ?? 0,
            "title" => $data['title'] ?? '',
            "cover" => $data['cover'] ?? '',
            "url" => $data['url'],
            "music" => $data['music'] ?? ["author" => "", "avatar" => ""],
        ];
    }
}

