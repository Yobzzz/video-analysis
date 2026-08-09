<?php

declare(strict_types=1);

namespace App\Services;

use App\Utils\Config;
use App\Utils\HttpClient;

/**
 * 视频指纹处理器
 *
 * 下载已解析的视频 → FFmpeg 重新编码（修改画面指纹+音频指纹+MD5）
 * 输出到 storage/processed/ 目录
 */
class VideoProcessor
{
    private string $ffmpegBin;
    private string $outputDir;
    private int $timeout;

    public function __construct()
    {
        $this->ffmpegBin  = Config::get('video_processor.ffmpeg_bin', 'ffmpeg');
        $this->outputDir  = rtrim(Config::get('video_processor.output_dir', __DIR__ . '/../../storage/processed'), '/');
        $this->timeout    = max(60, (int) Config::get('video_processor.timeout', 600));

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    /**
     * 处理视频：下载 → FFmpeg重编码 → 返回处理后文件路径
     *
     * @param string $sourceUrl 源视频 CDN 地址
     * @param string $filename  输出文件名（不含扩展名）
     * @param array  $options   自定义处理参数
     * @return array{url: string, size: int, md5: string, duration: float}
     */
        public function process(string $sourceUrl, string $filename = 'video', array $options = []): array
    {
        $outputFile = $this->outputDir . '/' . $this->safeName($filename) . '_processed.mp4';
        if (file_exists($outputFile) && filemtime($outputFile) > time() - 3600) {
            return $this->buildResult($outputFile, $filename);
        }
        $this->cleanOldFiles(10);
        $serverPort = $_SERVER['SERVER_PORT'] ?? '80'; $proxyUrl = 'http://localhost:' . $serverPort . '/index.php?action=media&url=' . urlencode($sourceUrl);
        $this->transcodeUrl($proxyUrl, $outputFile);
        return $this->buildResult($outputFile, $filename);
    }

    private function transcodeUrl(string $httpUrl, string $output): void
    {
        $cmd = sprintf('"%s" -y -i %s -c copy -map_metadata -1 -metadata title="proc" -movflags +faststart -f mp4 %s 2>&1', str_replace('"','',$this->ffmpegBin), escapeshellarg($httpUrl), escapeshellarg($output));
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) throw new \RuntimeException('FFmpeg启动失败');
        $stderr = ''; $startTime = time();
        stream_set_blocking($pipes[2], false);
        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) break;
            if (time() - $startTime > $this->timeout) { proc_terminate($process, 9); throw new \RuntimeException('FFmpeg超时'); }
            $chunk = fread($pipes[2], 4096);
            if ($chunk !== false && $chunk !== '') $stderr .= $chunk;
            usleep(100000);
        }
        $exitCode = $status['exitcode'] ?? -1;
        fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
        if ($exitCode !== 0 || !file_exists($output) || filesize($output) < 1024) { @unlink($output); throw new \RuntimeException('FFmpeg失败(code='.$exitCode.'): '.substr($stderr,-300)); }
        $header = @file_get_contents($output, false, null, 0, 12);
        if ($header === false || strpos($header, 'ftyp') === false) { @unlink($output); throw new \RuntimeException('输出非MP4'); }
    }

    private function transcode(string $input, string $output, array $options = []): void
    {
        // Render免费版512MB内存，重编码会OOM超时。改用流拷贝+元数据注入。
        // 效果：MD5完全改变，处理秒级完成。画面/音频指纹不变（需本地部署才能深度处理）。
        $cmd = sprintf(
            '"%s" -y -i %s -c copy -map_metadata -1 -metadata title="processed" -movflags +faststart -f mp4 %s 2>&1',
            str_replace('"', '', $this->ffmpegBin),
            escapeshellarg($input),
            escapeshellarg($output)
        );

        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('无法启动 FFmpeg 进程');
        }

        // 超时控制
        $startTime = time();
        $stderr = '';

        stream_set_blocking($pipes[2], false);
        while (true) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if (time() - $startTime > $this->timeout) {
                proc_terminate($process, 9);
                throw new \RuntimeException('FFmpeg 处理超时（' . $this->timeout . '秒）');
            }
            $chunk = fread($pipes[2], 4096);
            if ($chunk !== false && $chunk !== '') {
                $stderr .= $chunk;
            }
            usleep(100000); // 100ms
        }

        $exitCode = $status['exitcode'] ?? -1;
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        if ($exitCode !== 0 || !file_exists($output) || filesize($output) < 1024) {
            throw new \RuntimeException(
                'FFmpeg 处理失败 (code=' . $exitCode . '): ' . substr($cmd . "\nSTDERR: " . $stderr, -500)
            );
        }

        // 验证输出是可播放的 MP4（有 ftyp 头）
        $header = @file_get_contents($output, false, null, 0, 12);
        if ($header === false || strpos($header, 'ftyp') === false) {
            @unlink($output);
            throw new \RuntimeException('输出损坏，非有效MP4格式');
        }
    }

    /**
     * 下载远程视频到临时文件
     */
    private function download(string $url, string $dest): void
    {
        $fp = fopen($dest, 'wb');
        if (!$fp) {
            throw new \RuntimeException('无法创建临时文件');
        }

        // Use same anti-scraping headers as MediaProxy
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $scheme = $parts['scheme'] ?? 'https';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_FILE => $fp,
            CURLOPT_BUFFERSIZE => 262144,
            CURLOPT_TCP_NODELAY => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: */*',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Referer: ' . $scheme . '://' . $host . '/',
            ],
        ] + HttpClient::getSslOptions());

        // Validate we got actual video, not anti-scraping HTML
        $result = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($result === false || $httpCode >= 400) {
            @unlink($dest);
            throw new \RuntimeException('下载源视频失败 (HTTP ' . $httpCode . '): ' . $error);
        }

        if (filesize($dest) < 1024) {
            @unlink($dest);
            throw new \RuntimeException('下载的文件过小');
        }
    }

    /**
     * 组装返回结果
     */
    private function buildResult(string $file, string $filename): array
    {
        $size = filesize($file);
        $md5  = md5_file($file);

        // 获取视频时长
        $duration = 0.0;
        $cmd = sprintf(
            '%s -i %s -f null - 2>&1',
            escapeshellcmd($this->ffmpegBin),
            escapeshellarg($file)
        );

        $output = shell_exec($cmd);
        if ($output && preg_match('/Duration:\s*(\d+):(\d+):(\d+\.\d+)/', $output, $m)) {
            $duration = (int) $m[1] * 3600 + (int) $m[2] * 60 + (float) $m[3];
        }

        $cleanName = $this->safeName($filename) . '_processed.mp4';

        return [
            'url'      => '/dl/proc_' . urlencode($cleanName),
            'size'     => $size,
            'md5'      => $md5,
            'duration' => round($duration, 2),
            'filename' => $cleanName,
        ];
    }

    /**
     * 清理旧处理文件
     */
    private function cleanOldFiles(int $keep): void
    {
        $files = glob($this->outputDir . '/*_processed.mp4');
        if (!$files) return;

        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        foreach (array_slice($files, $keep) as $file) {
            @unlink($file);
        }
    }

    /**
     * 安全化文件名
     */
    private function safeName(string $name): string
    {
        $name = preg_replace('/[<>:"\/\\|?*]/u', '', $name);
        $name = trim(mb_substr($name, 0, 60, 'UTF-8'));
        return $name ?: 'video';
    }
}
