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
use App\Services\GameJob;
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

// ---- 后台拉起异步 worker ----
// 创建任务后脱离 php-fpm 请求进程组运行，使长耗时分析不再阻塞 HTTP 连接（根治移动端 499 超时）。
function spawnGameWorker(string $jobId): bool
{
    // 生产镜像（php:8.2-fpm）自带 /usr/local/bin/php CLI；本地回退到 PHP_BINARY。
    $phpBin = '/usr/local/bin/php';
    if (!is_executable($phpBin)) {
        $phpBin = PHP_BINARY ?: 'php';
    }
    $root = dirname(__DIR__, 2); // public/api → 仓库根
    $workerPath = $root . '/bin/worker.php';
    $logPath = $root . '/storage/jobs/' . $jobId . '.worker.log';
    // 校验依赖存在，否则走同步兜底，避免任务被静默卡在 pending。
    if (!is_file($workerPath) || !is_executable($phpBin)) {
        return false;
    }
    $worker = escapeshellarg($workerPath);
    $id = escapeshellarg($jobId);
    $log = escapeshellarg($logPath);

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows 仅为本地联调：start /B 不阻塞父进程
        $cmd = 'start /B ' . escapeshellarg($phpBin) . ' ' . $worker . ' ' . $id . ' > ' . $log . ' 2>&1';
        $h = @popen($cmd, 'r');
        if ($h) {
            pclose($h);
            return true;
        }
        return false;
    }
    // Linux：setsid 新建会话，配合 & 彻底脱离 php-fpm 进程组
    $cmd = 'setsid ' . escapeshellarg($phpBin) . ' ' . $worker . ' ' . $id . ' > ' . $log . ' 2>&1 &';
    @exec($cmd);
    return true;
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
    // 前端用户自定义的 AI 视觉模型配置（api_key / base_url / model），优先于全局 config
    $apiConfig = null;

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
        $tempDir = Config::get("game_analysis.temp_dir", __DIR__ . "/../../storage/tmp");
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        $dst = rtrim($tempDir, "/") . "/upload_" . bin2hex(random_bytes(8)) . ".mp4";
        if (!move_uploaded_file($_FILES["video"]["tmp_name"], $dst)) {
            Response::error("视频上传失败", 400);
        }
        $input["file"] = $dst;
        // 文件上传时 api_config 走 FormData 字段（JSON 字符串）
        if (!empty($_POST["api_config"])) {
            $ac = json_decode($_POST["api_config"], true);
            if (is_array($ac)) {
                $apiConfig = $ac;
            }
        }
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
            // JSON body 中的 api_config 对象
            if (isset($json["api_config"]) && is_array($json["api_config"])) {
                $apiConfig = $json["api_config"];
            }
        } elseif (!empty($_POST["url"])) {
            $input["url"] = trim($_POST["url"]);
        } elseif (!empty($_POST["demo"])) {
            $input["demo"] = true;
        }
    }

    // 透传用户自定义 API 配置给 GameAnalyzer（仅保留白名单字段，避免注入）
    if (is_array($apiConfig)) {
        $input["api_config"] = [
            "api_key"  => (string) ($apiConfig["api_key"] ?? ""),
            "base_url" => (string) ($apiConfig["base_url"] ?? ""),
            "model"    => (string) ($apiConfig["model"] ?? ""),
        ];
    }

    // 改为异步：创建任务 → 后台 worker 执行 → 立即返回 job_id（202），前端轮询进度。
    // 这样长耗时分析（下载 + 抽帧 + 视觉模型）不再阻塞 HTTP 连接，移动端不会再 499 超时。
    $jobId = GameJob::create($input);
    if (!spawnGameWorker($jobId)) {
        // 兜底：exec/setsid 不可用时同步执行（罕见），结果直接写入任务文件，前端轮询会立即读到完成。
        GameJob::run($jobId);
    }
    Response::success(["job_id" => $jobId], "已创建分析任务，正在后台处理", 202);
}

// GET /api/v1/game-job/{id} — 轮询异步任务进度 / 结果
if (preg_match('#^/api/v1/game-job/([A-Za-z0-9_\-]+)$#', $path, $m)) {
    if ($method !== "GET") {
        Response::error("请使用 GET 请求", 405);
    }
    $job = GameJob::get($m[1]);
    if (!$job) {
        Response::error("任务不存在或已过期", 404);
    }
    // 超时保护：处理中超过 20 分钟未更新视为 worker 崩溃，标记失败，避免前端无限轮询。
    if ($job["status"] === GameJob::STATUS_PROCESSING && (time() - (int) ($job["updatedAt"] ?? 0)) > 1200) {
        GameJob::fail($m[1], "分析超时（超过 20 分钟未完成，请重试或换更小的视频）");
        $job = GameJob::get($m[1]);
    }
    Response::success($job, "ok");
}

// POST /api/v1/game-test — 验证用户自定义 AI 视觉模型配置连通性
// 接收 {api_config:{api_key, base_url, model}}，发一个最小 chat completions ping
if ($path === "/api/v1/game-test" || $path === "/api/v1/game-test/") {
    if ($method !== "POST") {
        Response::error("请使用 POST 请求", 405);
    }
    $body = file_get_contents("php://input");
    $json = ($body !== false && $body !== "") ? json_decode($body, true) : null;
    $ac = is_array($json) && isset($json["api_config"]) && is_array($json["api_config"]) ? $json["api_config"] : null;
    if (!$ac || empty($ac["api_key"])) {
        Response::error("请提供 api_config（含 api_key）", 400);
    }
    $apiKey = (string) $ac["api_key"];
    $baseUrl = rtrim((string) ($ac["base_url"] ?? "https://api.openai.com/v1"), "/");
    $model = (string) ($ac["model"] ?? "gpt-4o-mini");
    try {
        $respHeaders = null;
        // ping 应当很快，但国内 API 从海外服务器连过去握手延迟高，放宽连接/总超时，避免误判"连接失败"。
        $resp = \App\Utils\HttpClient::request(
            $baseUrl . "/chat/completions",
            json_encode([
                "model" => $model,
                "messages" => [["role" => "user", "content" => "ping"]],
                "max_tokens" => 5,
            ], JSON_UNESCAPED_UNICODE),
            [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $apiKey,
            ],
            0, $respHeaders, 15, 30
        );
        if (!$resp["success"]) {
            Response::error("视觉模型连接失败：" . ($resp["error"] ?: "HTTP " . ($resp["http_code"] ?? 0)), 500);
        }
        Response::success(["model" => $model], "连接正常，视觉模型可用");
    } catch (\RuntimeException $e) {
        Response::error($e->getMessage(), 500);
    } catch (\Throwable $e) {
        Response::error("测试异常：" . $e->getMessage(), 500);
    }
}

// 404
Response::error("接口不存在", 404);

