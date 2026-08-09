<?php

declare(strict_types=1);

namespace App\Services;

use App\Utils\Config;
use App\Utils\HttpClient;

/**
 * 视频处理服务 —— 下载CDN视频并保存为处理后文件
 */
class VideoProcessor
{
    private string $outputDir;
    private int $timeout;

    public function __construct()
    {
        $this->outputDir = rtrim(Config::get('video_processor.output_dir', __DIR__ . '/../../storage/processed'), '/');
        if (!is_dir($this->outputDir)) {
            if (!@mkdir($this->outputDir, 0755, true) && !is_dir($this->outputDir)) {
                // Render只读文件系统fallback到/tmp
                $this->outputDir = '/tmp/video-processed';
                @mkdir($this->outputDir, 0755, true);
            }
        }
        $this->timeout   = max(30, (int) Config::get('video_processor.timeout', 300));
    }

    public function process(string $sourceUrl, string $filename = 'video', array $options = []): array
    {
        $safeName = $this->safeName($filename);
        $outputFile = $this->outputDir . '/' . $safeName . '_processed.mp4';

        if (file_exists($outputFile) && filemtime($outputFile) > time() - 3600) {
            return $this->buildResult($outputFile, $safeName);
        }

        $this->cleanOldFiles(10);

        // Direct curl download from CDN with media-proxy-compatible headers
        $fp = @fopen($outputFile, 'wb');
        if (!$fp) throw new \RuntimeException('无法创建输出文件');

        $ch = curl_init($sourceUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_FILE => $fp,
            CURLOPT_BUFFERSIZE => 262144,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: */*',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Referer: https://www.douyin.com/',
            ],
        ] + (class_exists('App\Utils\HttpClient') ? HttpClient::getSslOptions() : [CURLOPT_SSL_VERIFYPEER => false]));

        curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($httpCode >= 400 || !file_exists($outputFile) || filesize($outputFile) < 2048) {
            @unlink($outputFile);
            throw new \RuntimeException('下载失败 HTTP ' . $httpCode . ($error ? ': ' . $error : ''));
        }

        // Detect anti-scraping HTML response
        $header = @file_get_contents($outputFile, false, null, 0, 5);
        if ($header === false || strpos($header, '<') === 0) {
            @unlink($outputFile);
            throw new \RuntimeException('CDN返回了HTML而不是视频，请重试');
        }

        return $this->buildResult($outputFile, $safeName);
    }

    private function safeName(string $name): string
    {
        $name = preg_replace('/[<>:"\/\\\\|?*]/u', '', $name);
        $name = trim(mb_substr($name, 0, 60, 'UTF-8'));
        return $name ?: 'video';
    }

    private function cleanOldFiles(int $keep = 10): void
    {
        $files = glob($this->outputDir . '/*_processed.mp4');
        if (!$files || count($files) <= $keep) return;
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
        foreach (array_slice($files, 0, count($files) - $keep) as $f) {
            @unlink($f);
        }
    }

    private function buildResult(string $file, string $safeName): array
    {
        $cleanName = $safeName . '_processed.mp4';
        return [
            'url'      => '/dl/proc_' . urlencode($cleanName),
            'size'     => filesize($file),
            'md5'      => md5_file($file),
            'duration' => 0,
            'filename' => $cleanName,
        ];
    }
}
