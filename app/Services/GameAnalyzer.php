<?php

namespace App\Services;

use App\Services\Vision\LocalHeuristicProvider;
use App\Services\Vision\OpenAIVisionProvider;
use App\Services\Vision\VisionProviderInterface;
use App\Utils\Config;
use App\Utils\FFmpeg;
use App\Utils\HttpClient;
use App\Utils\Logger;

/**
 * 小游戏视频分析编排器
 *
 * 流程：定位视频（平台链接解析 / 直链下载 / 本地文件）
 *  → ffmpeg 抽帧 + 场景变化检测
 *  → 视觉 Provider 理解画面（离线启发式 / OpenAI 视觉）
 *  → 生成口播配音稿
 */
class GameAnalyzer
{
    /**
     * @param array $input ['url'=>string] | ['file'=>string 本地路径] | ['demo'=>true]
     * @return array 结构化结果
     */
    public static function analyze(array $input): array
    {
        if (!empty($input['demo'])) {
            return self::demo();
        }

        if (Config::get('game_analysis.enabled', true) === false) {
            throw new \RuntimeException('小游戏分析功能未启用');
        }

        // Windows 内置开发服务器（php -S）在请求内调用 ffmpeg 会把监听套接字继承给子进程，
        // 导致 worker 崩溃（平台限制，Apache/Nginx + PHP-FPM 不受影响）。真实视频分析需部署到正式环境。
        if (PHP_OS_FAMILY === 'Windows' && php_sapi_name() === 'cli-server') {
            throw new \RuntimeException('当前为 Windows 内置开发服务器（php -S），无法在请求内调用 ffmpeg 抽帧（会中断服务器）。请勾选「演示数据」体验效果，或将真实视频分析部署到 Nginx/Apache + PHP-FPM 后使用。');
        }

        $platformInfo = [];
        $videoFile = self::resolveVideoFile($input, $platformInfo);
        $downloaded = ($videoFile !== ($input['file'] ?? null));

        try {
            $ffmpeg = new FFmpeg();
            if (!$ffmpeg->available()) {
                throw new \RuntimeException('未找到 ffmpeg，无法抽取画面');
            }

            $tempDir = self::tempDir();
            $interval = (int) Config::get('game_analysis.frame_interval', 2);
            $threshold = (float) Config::get('game_analysis.scene_threshold', 0.35);
            $maxFrames = (int) Config::get('game_analysis.max_frames', 12);
            $thumbWidth = (int) Config::get('game_analysis.thumb_width', 320);

            $duration = $ffmpeg->duration($videoFile);
            $scenes = $ffmpeg->detectScenes($videoFile, $threshold);
            // 短视频自适应加密采样：保证约 10 帧，避免 6 秒视频只抽出 3 帧
            if ($duration > 0) {
                $adaptive = max(1, (int) round($duration / 10));
                $interval = min($interval, $adaptive);
            }
            $rawFrames = $ffmpeg->extractIntervalFrames($videoFile, $tempDir, $interval, $maxFrames, $thumbWidth);

            $frames = [];
            foreach ($rawFrames as $f) {
                if (is_file($f['path'])) {
                    $frames[] = [
                        't' => $f['t'],
                        'dataUrl' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($f['path'])),
                    ];
                    @unlink($f['path']);
                }
            }

            $meta = [
                'duration' => $duration,
                'scenes' => $scenes,
                'frames' => $frames,
                'title' => $platformInfo['title'] ?? '',
                'author' => $platformInfo['author'] ?? '',
                'cover' => $platformInfo['cover'] ?? '',
            ];
            $provider = self::makeProvider($input['api_config'] ?? []);
            $analysis = $provider->analyze($videoFile, $meta);

            if (empty($analysis['script'])) {
                $narration = GameNarrationGenerator::generate($analysis, $meta);
                $script = $narration['script'];
                $title = $narration['title'];
            } else {
                $script = $analysis['script'];
                $title = self::titleFromSummary($analysis['summary'] ?? '', $meta);
            }

            return [
                'duration' => round($duration, 1),
                'frames' => $frames,
                'segments' => $analysis['segments'],
                'summary' => $analysis['summary'] ?? '',
                'script' => $script,
                'title' => $title,
                'provider' => $analysis['provider'] ?? $provider->name(),
            ];
        } finally {
            if ($downloaded && is_file($videoFile)) {
                @unlink($videoFile);
            }
        }
    }

    /**
     * 视觉模型模式下，标题取内容总览（更贴合视频实际内容）
     */
    private static function titleFromSummary(string $summary, array $meta): string
    {
        $summary = trim($summary);
        if ($summary === '') {
            return trim((string) ($meta['title'] ?? '')) ?: '视频内容分析';
        }
        $title = mb_substr($summary, 0, 22);
        return mb_strlen($summary) > 22 ? $title . '…' : $title;
    }

    /**
     * 解析视频来源，返回本地可读的视频文件路径
     */
    private static function resolveVideoFile(array $input, array &$platformInfo = []): string
    {
        if (!empty($input['file']) && is_file($input['file'])) {
            return $input['file'];
        }
        if (empty($input['url'])) {
            throw new \InvalidArgumentException('请提供视频链接或上传视频文件');
        }

        $url = trim((string) $input['url']);
        // 分享文本兜底：整段粘贴的分享文案（含"复制打开抖音…"等）里提取第一个 URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            if (preg_match('#https?://[^\s"\'<>，。；：！？【】（）《》]+#i', $url, $m)) {
                $url = rtrim($m[0], '),。');
            }
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('无效的视频链接');
        }

        // 平台分享链接 → 解析出无水印直链
        $cdnUrl = $url;
        try {
            $parsed = (new VideoParser())->parse($url);
            if (!empty($parsed['url'])) {
                $cdnUrl = $parsed['url'];
            }
            $platformInfo = [
                'title' => $parsed['title'] ?? '',
                'author' => $parsed['author'] ?? '',
                'cover' => $parsed['cover'] ?? '',
            ];
        } catch (\Throwable $e) {
            Logger::info('平台链接解析失败，尝试直连下载', ['url' => $url, 'error' => $e->getMessage()]);
        }

        $tempDir = self::tempDir();
        $dst = $tempDir . '/game_' . bin2hex(random_bytes(8)) . '.mp4';
        $host = strtolower((string) parse_url($cdnUrl, PHP_URL_HOST));
        $resp = HttpClient::request($cdnUrl, null, [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Referer' => MediaProxy::refererForHost($host),
        ], 2);
        if (!$resp['success'] || empty($resp['data'])) {
            $hint = self::downloadHint($url);
            throw new \RuntimeException('视频下载失败：' . ($resp['error'] ?: '空响应') . $hint);
        }
        file_put_contents($dst, $resp['data']);
        if (!is_file($dst) || filesize($dst) < 1024 || !self::looksLikeVideo($resp['data'])) {
            @unlink($dst);
            $hint = self::downloadHint($url);
            throw new \RuntimeException('下载内容不是可解析的视频文件（可能是网页被风控拦截）' . $hint);
        }
        return $dst;
    }

    /**
     * 根据输入链接给出下载失败的针对性提示
     */
    private static function downloadHint(string $url): string
    {
        if (preg_match('/douyin\.com|iesdouyin\.com/i', $url)) {
            return '。抖音接口风控严格：请在 .env 配置 DOUYIN_COOKIE（浏览器登录抖音后复制的 Cookie）后重试';
        }
        return '';
    }

    /**
     * 通过文件头魔数判断内容是否视频容器（mp4/mov/flv/webm/mkv/avi）
     */
    private static function looksLikeVideo(string $data): bool
    {
        $head = substr($data, 0, 64);
        // MP4/MOV: 前4字节为 box size，后4字节为 'ftyp'
        if (strlen($head) >= 12 && substr($head, 4, 4) === 'ftyp') {
            return true;
        }
        // FLV
        if (substr($head, 0, 3) === 'FLV') {
            return true;
        }
        // WebM/Matroska EBML
        if (substr($head, 0, 4) === "\x1A\x45\xDF\xA3") {
            return true;
        }
        // MPEG-TS
        if (substr($head, 0, 4) === "\x47\x40\x00\x00" || substr($head, 0, 4) === "\x47\x40\x00\x10") {
            return true;
        }
        return false;
    }

    /**
     * 选择视觉 Provider
     *
     * 优先级：前端用户自定义 api_config（api_key 非空）> 全局配置（GAME_ANALYSIS_PROVIDER + OPENAI_API_KEY）
     * 用户在前端填入自己的 API Key 时，覆盖全局配置，让每个用户用自己的视觉模型额度。
     *
     * @param array $overrides 前端传入的用户自定义配置 ['api_key'=>, 'base_url'=>, 'model'=>]
     */
    private static function makeProvider(array $overrides = []): VisionProviderInterface
    {
        // 前端用户自定义了 API Key → 直接启用视觉模型（覆盖全局 provider 配置）
        $userKey = (string) ($overrides['api_key'] ?? '');
        if ($userKey !== '') {
            return new OpenAIVisionProvider($overrides);
        }

        $provider = (string) Config::get('game_analysis.provider', 'auto');
        $key = (string) (Config::get('game_analysis.openai', [])['api_key'] ?? '');
        // auto：配置了 API Key 时自动启用视觉模型（真正读画面理解内容）
        if (($provider === 'openai' || $provider === 'auto') && $key !== '') {
            return new OpenAIVisionProvider();
        }
        return new LocalHeuristicProvider();
    }

    private static function tempDir(): string
    {
        $dir = (string) Config::get('game_analysis.temp_dir', __DIR__ . '/../../storage/tmp');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * 离线演示结果（无需 ffmpeg / 网络），用于本地联调前端
     */
    public static function demo(): array
    {
        $meta = [
            'duration' => 42.0,
            'scenes' => [
                ['t' => 6, 'score' => 0.40],
                ['t' => 14, 'score' => 0.62],
                ['t' => 22, 'score' => 0.45],
                ['t' => 31, 'score' => 0.58],
                ['t' => 38, 'score' => 0.30],
            ],
            'frames' => [],
        ];
        $analysis = (new LocalHeuristicProvider())->analyze('', $meta);
        $narration = GameNarrationGenerator::generate($analysis, $meta);
        return [
            'duration' => 42.0,
            'frames' => [],
            'segments' => $analysis['segments'],
            'summary' => $analysis['summary'] ?? '',
            'script' => $narration['script'],
            'title' => $narration['title'],
            'provider' => 'demo(heuristic)',
        ];
    }
}
