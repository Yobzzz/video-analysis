<?php

declare(strict_types=1);

/**
 * API v1 入口
 *
 * 版本化 API 路由，支持：
 * - GET /api/v1/parse?url=...
 * - POST /api/v1/parse (url in body)
 * - GET /api/v1/platforms
 * - GET /api/v1/health
 */

require_once __DIR__ . "/../../vendor/autoload.php";

use App\Services\VideoParser;
use App\Services\MediaProxy;
use App\Services\GameAnalyzer;
use App\Services\RateLimiter;
use App\Utils\Response;
use App\Utils\Config;
use App\Utils\Logger;

// CORS
$corsOrigin = Config::get("app.cors_allow_origin", "*");
header("Access-Control-Allow-Origin: " . $corsOrigin);
header("Access-Control-Allow-Methods: GET, POST, HEAD, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key");
header("Vary: Origin");
header("Content-Type: application/json; charset=utf-8");

$method = strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");

if ($method === "OPTIONS") {
    http_response_code(204);
    exit;
}

if (!in_array($method, ["GET", "POST", "HEAD"], true)) {
    Response::error("不支持的请求方法", 405);
}

// HEAD 请求直接返回 200（健康检查/监控用）
if ($method === "HEAD") {
    http_response_code(200);
    exit;
}

// Parse the route from REQUEST_URI
$requestUri = $_SERVER["REQUEST_URI"] ?? "/";
$path = parse_url($requestUri, PHP_URL_PATH);
$path = rtrim($path, "/");

// Rate limiting
$ip = RateLimiter::getClientIp();

// ---- API Key 鉴权（可选开启，默认关闭）----
// health 保持公开，便于健康检查；其余接口按配置校验 X-API-Key 或 Authorization: Bearer
$path = rtrim($path, "/");
if ($path !== "/api/v1/health" && $path !== "/api/v1/health/") {
    $keyEnabled = Config::get("api.key_enabled", false);
    if ($keyEnabled) {
        $apiKey = Config::get("api.key", "");
        $provided = $_SERVER["HTTP_X_API_KEY"] ?? "";
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? "";
        if ($provided === "") {
            if (preg_match("/^Bearer\s+(.+)$/i", $authHeader, $m)) {
                $provided = trim($m[1]);
            }
        }
        if ($apiKey === "" || !hash_equals($apiKey, $provided)) {
            Response::error("无效或缺失 API Key", 401);
        }
    }
}

// ---- Routes ----

// GET /api/v1/health
if ($path === "/api/v1/health" || $path === "/api/v1/health/") {
    Response::success([
        "status" => "ok",
        "version" => "2.0.0",
        "php_version" => PHP_VERSION,
        "timestamp" => time(),
    ], "服务正常");
}

// GET /api/v1/platforms
if ($path === "/api/v1/platforms" || $path === "/api/v1/platforms/") {
    $parser = new VideoParser();
    Response::success($parser->getSupportedPlatforms(), "支持平台列表");
}

// GET/POST /api/v1/parse
if ($path === "/api/v1/parse" || $path === "/api/v1/parse/") {
    // Rate limit check
    if (Config::get("rate_limit.enabled", true)) {
        $rateLimitResult = RateLimiter::check($ip);
        if (!$rateLimitResult["allowed"]) {
            Response::error("请求过于频繁，请稍后再试", 429);
        }
    }

    // Get URL parameter
    $url = trim($_GET["url"] ?? $_POST["url"] ?? "");

    // Also support JSON body
    if ($url === "" && $method === "POST") {
        $body = file_get_contents("php://input");
        if ($body !== false && $body !== "") {
            $json = json_decode($body, true);
            if (is_array($json) && !empty($json["url"])) {
                $url = trim($json["url"]);
            }
        }
    }

    if ($url === "") {
        Response::validationError(["url" => "URL 参数不能为空"]);
    }

    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match("/^https?:\/\//i", $url)) {
        Response::validationError(["url" => "无效的 URL 格式"]);
    }

    try {
        $parser = new VideoParser();
        $data = $parser->parse($url);
        Response::success($data, "解析成功");
    } catch (\InvalidArgumentException $e) {
        Response::error($e->getMessage(), 400);
    } catch (\RuntimeException $e) {
        Logger::error("解析失败", ["url" => $url, "ip" => $ip, "error" => $e->getMessage()]);
        Response::error($e->getMessage(), 500);
    } catch (\Throwable $e) {
        Logger::exception($e, ["url" => $url, "ip" => $ip]);
        Response::error("服务器内部错误，请稍后再试", 500);
    }
}

// GET /api/v1/download-proxy — 直通下载（绕过MediaProxy，用于Kuaishou等被封CDN）
if ($path === "/api/v1/download-proxy" || $path === "/api/v1/download-proxy/") {
    $dlUrl = trim($_GET["url"] ?? "");
    if ($dlUrl === "" || !filter_var($dlUrl, FILTER_VALIDATE_URL)) {
        Response::error("URL 参数不能为空", 400);
    }
    $dlName = trim($_GET["filename"] ?? "video.mp4");
    $dlName = preg_replace('/[<>:"\/\\\\|?*]/u', '', $dlName) ?: 'video.mp4';
    if (!preg_match('/\.mp4$/i', $dlName)) {
        $dlName = preg_replace('/\.[^.]+$/i', '', $dlName) . '.mp4';
    }

    // 用 curl 直连 CDN 下载并流式输出
    $ch = curl_init($dlUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Referer: https://www.kuaishou.com/', 'Accept: */*'],
        CURLOPT_WRITEFUNCTION => function ($ch, $data) {
            echo $data;
            return strlen($data);
        },
    ] + (class_exists('App\Utils\HttpClient') ? \App\Utils\HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));

    header('Content-Type: video/mp4');
    header('Content-Disposition: attachment; filename="' . addslashes($dlName) . '"; filename*=UTF-8\'\'' . rawurlencode($dlName));
    header('Accept-Ranges: bytes');
    curl_exec($ch);
    curl_close($ch);
    exit;
}

// POST /api/v1/game-analysis — 小游戏视频分析（AI 读画面变化 → 口播配音稿）
if ($path === "/api/v1/game-analysis" || $path === "/api/v1/game-analysis/") {
    if ($method !== "POST") {
        Response::error("请使用 POST 请求", 405);
    }
    if (Config::get("rate_limit.enabled", true)) {
        $rateLimitResult = RateLimiter::check($ip);
        if (!$rateLimitResult["allowed"]) {
            Response::error("请求过于频繁，请稍后再试", 429);
        }
    }

    $input = [];
    // 1) 文件上传（本地视频）
    if (!empty($_FILES["video"]) && isset($_FILES["video"]["error"])) {
        $uploadErr = (int) $_FILES["video"]["error"];
        if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
            Response::error("视频文件过大，请上传不超过 " . strtoupper(ini_get("upload_max_filesize")) . "B 的视频后重试", 400);
        } elseif ($uploadErr !== UPLOAD_ERR_OK && $uploadErr !== UPLOAD_ERR_NO_FILE) {
            Response::error("视频上传失败（错误码 {$uploadErr}），请重试或更换文件", 400);
        }
    }
    if (!empty($_FILES["video"]) && is_uploaded_file($_FILES["video"]["tmp_name"] ?? "")) {
        $dst = (Config::get("game_analysis.temp_dir", __DIR__ . "/../../storage/tmp"))
            . "/upload_" . bin2hex(random_bytes(8)) . ".mp4";
        if (!move_uploaded_file($_FILES["video"]["tmp_name"], $dst)) {
            Response::error("视频上传失败", 400);
        }
        $input["file"] = $dst;
    } else {
        // 2) JSON / 表单 body（demo 或 url）
        $body = file_get_contents("php://input");
        $json = ($body !== false && $body !== "") ? json_decode($body, true) : null;
        if (is_array($json)) {
            if (!empty($json["demo"])) {
                $input["demo"] = true;
            } elseif (!empty($json["url"])) {
                $input["url"] = trim($json["url"]);
            }
        } elseif (!empty($_POST["url"])) {
            $input["url"] = trim($_POST["url"]);
        } elseif (!empty($_POST["demo"])) {
            $input["demo"] = true;
        }
    }

    try {
        $result = GameAnalyzer::analyze($input);
        if (isset($input["file"]) && is_file($input["file"])) {
            @unlink($input["file"]);
        }
        Response::success($result, "分析完成");
    } catch (\InvalidArgumentException $e) {
        Response::error($e->getMessage(), 400);
    } catch (\RuntimeException $e) {
        Logger::error("小游戏分析失败", ["error" => $e->getMessage()]);
        Response::error($e->getMessage(), 500);
    } catch (\Throwable $e) {
        Logger::exception($e, ["ip" => $ip]);
        Response::error("服务器内部错误，请稍后再试", 500);
    }
}

// 404
Response::error("接口不存在", 404);

