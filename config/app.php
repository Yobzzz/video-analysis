<?php

use App\Utils\Config;

// 确保环境变量已加载
Config::env('APP_NAME');

/**
 * 应用配置文件
 */
return [
    // 应用基础配置
    'app' => [
        'name' => Config::env('APP_NAME', 'Video Analysis'),
        'debug' => Config::env('APP_DEBUG', 'false') === 'true',
        'cors_allow_origin' => Config::env('APP_CORS_ALLOW_ORIGIN', '*'),
        'env' => Config::env('APP_ENV', 'production'), // production, staging, local
    ],

    // API 速率限制配置
    'rate_limit' => [
        'enabled' => Config::env('RATE_LIMIT_ENABLED', 'true') !== 'false',
        'max_requests' => (int)Config::env('RATE_LIMIT_MAX_REQUESTS', 60),
        'time_window' => (int)Config::env('RATE_LIMIT_TIME_WINDOW', 60),
        'trust_proxy_headers' => Config::env('RATE_LIMIT_TRUST_PROXY_HEADERS', 'false') === 'true',
    ],

    // API Key 鉴权（可选开启）
    'api' => [
        'key_enabled' => Config::env('API_KEY_ENABLED', 'false') === 'true',
        'key' => Config::env('API_KEY', ''),
    ],

    // HTTP 客户端配置
    'curl' => [
        'connect_timeout' => (int)Config::env('CURL_CONNECT_TIMEOUT', 5),
        'timeout' => (int)Config::env('CURL_TIMEOUT', 10),
        'max_retries' => (int)Config::env('CURL_MAX_RETRIES', 3),
        'cafile' => Config::env('CURL_CA_BUNDLE', __DIR__ . '/../storage/certs/cacert.pem'),
    ],

    // 媒体代理配置
    'media_proxy' => [
        'enabled' => Config::env('MEDIA_PROXY_ENABLED', 'true') !== 'false',
        'max_file_size' => (int)Config::env('MEDIA_PROXY_MAX_SIZE', '524288000'), // 500MB
        'allowed_domains' => array_filter(array_map('trim', explode(',', Config::env('MEDIA_PROXY_ALLOWED_DOMAINS', '')))),
    ],

    // 视频指纹处理
    'video_processor' => [
        'ffmpeg_bin'  => Config::env('FFMPEG_BIN', 'ffmpeg'),
        'output_dir'  => Config::env('STORAGE_PROCESSED_DIR', __DIR__ . '/../storage/processed'),
        'timeout'     => (int)Config::env('FFMPEG_TIMEOUT', '600'),
    ],

    // 日志配置
    'logging' => [
        'level' => Config::env('LOG_LEVEL', 'error'), // debug, info, warning, error
        'file' => __DIR__ . '/../../storage/logs/app.log',
        'max_files' => (int)Config::env('LOG_MAX_FILES', '30'),
    ],

    // 小游戏视频分析（AI 读画面变化 → 生成口播配音稿）
    'game_analysis' => [
        'enabled' => Config::env('GAME_ANALYSIS_ENABLED', 'true') !== 'false',
        'provider' => Config::env('GAME_ANALYSIS_PROVIDER', 'auto'), // auto | heuristic | openai（auto: 配置了 OPENAI_API_KEY 即用视觉模型）
        // ffmpeg 路径：优先 env，其次自动探测常见安装位置
        'ffmpeg_bin' => Config::env('GAME_FFMPEG_BIN', Config::env('FFMPEG_BIN', 'ffmpeg')),
        'frame_interval' => (int)Config::env('GAME_FRAME_INTERVAL', 2),   // 视觉采样间隔(秒)
        'scene_threshold' => (float)Config::env('GAME_SCENE_THRESHOLD', 0.35), // 场景切换阈值
        'max_frames' => (int)Config::env('GAME_MAX_FRAMES', 12),          // 抽帧/采样上限
        'thumb_width' => (int)Config::env('GAME_THUMB_WIDTH', 640),       // 缩略图宽度（视觉模型读图细节）
        'temp_dir' => Config::env('GAME_TEMP_DIR', __DIR__ . '/../storage/tmp'),
        'openai' => [
            'api_key' => Config::env('OPENAI_API_KEY', ''),
            'base_url' => Config::env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => Config::env('GAME_OPENAI_MODEL', 'gpt-4o-mini'),
            // 视觉模型推理（多帧 + 长输出）耗时远高于普通接口；Render 等海外服务器连接国内 API 延迟也高。
            // 这里放宽超时，避免 10s 触发 Operation timed out。可用环境变量覆盖。
            'connect_timeout' => (int) Config::env('GAME_OPENAI_CONNECT_TIMEOUT', 15),
            'timeout' => (int) Config::env('GAME_OPENAI_TIMEOUT', 120),
        ],
    ],
];

