<?php

declare(strict_types=1);

/**
 * PHP 内置服务器路由脚本（composer serve 使用）
 *
 * 与 public/.htaccess 保持一致的转发规则：
 * - 存在的静态文件直接返回
 * - /api/v1/* -> api/v1.php
 * - 其余 -> index.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false;
}

if (strpos($uri, '/api/v1/') === 0) {
    require __DIR__ . '/api/v1.php';
    return true;
}

// Serve processed video files from storage/processed/
if (strpos($uri, '/storage/processed/') === 0) {
    $storageFile = __DIR__ . '/..' . $uri;
    if (file_exists($storageFile) && is_file($storageFile)) {
        header('Content-Type: video/mp4');
        header('Content-Length: ' . filesize($storageFile));
        header('Accept-Ranges: bytes');
        readfile($storageFile);
        return true;
    }
}

// /dl/*.mp4 → media proxy download
if (strpos($uri, '/dl/') === 0 && preg_match('/\.mp4$/i', $uri)) {
    $filename = basename($uri);
    $mediaUrl = $_GET['url'] ?? '';
    // Forward to index.php with media action
    $_GET['action'] = 'media';
    $_GET['url'] = $mediaUrl;
    $_GET['download'] = '1';
    $_GET['filename'] = $filename;
    require __DIR__ . '/index.php';
    return true;
}

require __DIR__ . '/index.php';
return true;
