<?php

namespace App\Utils;

/**
 * ffmpeg / ffprobe 封装
 *
 * 负责：探测可执行文件、读取视频时长、按固定间隔抽帧、检测场景切换（画面变化）。
 * 所有命令均使用转义后的参数（escapeshellarg），避免命令注入。
 *
 * 说明：Windows 上的 PHP 内置开发服务器（php -S）在请求处理中调用外部进程时，
 * 会把监听套接字继承给子进程，导致 worker 崩溃（仅影响本地 `php -S` 调试）。
 * 该问题在 Apache / Nginx + PHP-FPM 生产环境不会出现，故此处使用稳定的 shell_exec 实现。
 */
class FFmpeg
{
    private string $bin;

    public function __construct(?string $bin = null)
    {
        $this->bin = $bin ?: self::resolve();
    }

    /**
     * 解析 ffmpeg 可执行文件路径：配置 > 自动探测常见安装位置 > 回退 'ffmpeg'
     */
    public static function resolve(): string
    {
        $cfg = (string) (Config::get('game_analysis.ffmpeg_bin') ?: Config::get('video_processor.ffmpeg_bin') ?: 'ffmpeg');
        if ($cfg !== 'ffmpeg' && is_file($cfg)) {
            return $cfg;
        }
        $candidates = [
            'C:/Program Files (x86)/推兔/source/ffmpeg.exe',
            'E:/JianyingPro/10.5.0.13988/ffmpeg.exe',
            'E:/推兔/source/ffmpeg.exe',
            'C:/Users/Administrator/AppData/Roaming/YMMedia/plugin/ffmpeg/ffmpeg.exe',
            'C:/Users/Administrator/AppData/Roaming/YMMedia/plugin/ffmpeg_7_1/ffmpeg.exe',
        ];
        // PHP shell_exec 走 cmd.exe，不识别 MSYS 风格 /c/、/e/ 路径；统一转换为 Windows 盘符
        foreach ($candidates as &$c) {
            $c = preg_replace('#^/([a-z])/#i', '$1:/', $c);
        }
        unset($c);
        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $c;
            }
        }
        return $cfg;
    }

    public function bin(): string
    {
        return $this->bin;
    }

    public function available(): bool
    {
        $out = @shell_exec('"' . $this->bin . '" -version 2>&1');
        return $out !== null && stripos($out, 'ffmpeg') !== false;
    }

    /**
     * 读取视频时长（秒）
     */
    public function duration(string $file): float
    {
        $out = @shell_exec('"' . $this->bin . '" -hide_banner -i ' . escapeshellarg($file) . ' 2>&1');
        if ($out && preg_match('/Duration:\s*(\d+):(\d+):(\d+(?:\.\d+)?)/', $out, $m)) {
            return (float) $m[1] * 3600 + (float) $m[2] * 60 + (float) $m[3];
        }
        return 0.0;
    }

    /**
     * 按固定间隔抽取代表帧（用于视觉模型采样 / 缩略图）
     *
     * @return array<int, array{ t: float, path: string }>
     */
    public function extractIntervalFrames(string $file, string $outDir, int $interval, int $maxFrames, int $thumbWidth): array
    {
        $duration = $this->duration($file);
        if ($duration <= 0) {
            return [];
        }
        $count = min($maxFrames, max(1, (int) floor($duration / max(1, $interval))));
        if (!is_dir($outDir)) {
            mkdir($outDir, 0777, true);
        }
        $frames = [];
        for ($i = 1; $i <= $count; $i++) {
            $t = round($duration * $i / ($count + 1), 2);
            $path = $outDir . '/frame_' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '.jpg';
            $cmd = '"' . $this->bin . '" -y -ss ' . escapeshellarg((string) $t)
                . ' -i ' . escapeshellarg($file)
                . ' -frames:v 1 -vf scale=' . ((int) $thumbWidth) . ':-1 -q:v 4 '
                . escapeshellarg($path) . ' 2>&1';
            @shell_exec($cmd);
            if (is_file($path)) {
                $frames[] = ['t' => $t, 'path' => $path];
            }
        }
        return $frames;
    }

    /**
     * 检测场景切换（画面变化）时间点及变化强度
     *
     * @return array<int, array{ t: float, score: float }>
     */
    public function detectScenes(string $file, float $threshold = 0.35): array
    {
        $cmd = '"' . $this->bin . '" -hide_banner -i ' . escapeshellarg($file)
            . ' -vf "select=gt(scene\,' . number_format($threshold, 2, '.', '') . '),metadata=print:file=-:key=lavfi.scene_score,showinfo"'
            . ' -f null - 2>&1';
        $out = @shell_exec($cmd);
        if (!$out) {
            return [];
        }
        $scenes = [];
        $scores = [];
        foreach (explode("\n", $out) as $line) {
            if (preg_match('/pts_time:\s*([\d.]+)/', $line, $m)) {
                $scenes[] = (float) $m[1];
            }
            if (preg_match('/lavfi\.scene_score\s*=\s*([\d.]+)/', $line, $m)) {
                $scores[] = (float) $m[1];
            }
        }
        $result = [];
        foreach ($scenes as $i => $t) {
            $result[] = ['t' => $t, 'score' => $scores[$i] ?? $threshold];
        }
        return $result;
    }
}
