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

// POST /api/v1/process — 视频指纹重编码
if ($path === "/api/v1/process" || $path === "/api/v1/process/") {
    if ($method !== "POST") {
        Response::error("请使用 POST 方法", 405);
    }

    $sourceUrl = trim($_POST["url"] ?? "");
    $filename  = trim($_POST["filename"] ?? "video");

    if ($sourceUrl === "" || !filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
        Response::validationError(["url" => "无效的视频链接"]);
    }

    try {
        $processor = new \App\Services\VideoProcessor();
        $result = $processor->process($sourceUrl, $filename, [
            'noise_alls'  => (int) ($_POST["noise"] ?? 3),
            'brightness'  => (float) ($_POST["brightness"] ?? 0.05),
            'contrast'    => (float) ($_POST["contrast"] ?? 1.04),
            'saturation'  => (float) ($_POST["saturation"] ?? 1.06),
            'crf'         => (int) ($_POST["crf"] ?? 24),
            'audio_rate'  => (int) ($_POST["audio_rate"] ?? 48000),
        ]);
        Response::success($result, "处理完成");
    } catch (\RuntimeException $e) {
        Response::error($e->getMessage(), 500);
    }
}

// 404
Response::error("接口不存在", 404);

