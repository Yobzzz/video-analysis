<?php

namespace App\Services;

use App\Utils\Config;
use App\Utils\Logger;

/**
 * 口播稿异步分析任务存储
 *
 * 任务以 JSON 文件形式存于 storage/jobs/{id}.json，包含：
 *  status    pending | processing | completed | failed
 *  progress  0-100
 *  stage     阶段标识（queued/downloading/extracting/analyzing/finalizing/done/failed）
 *  stageText 给前端展示的中文阶段文案
 *  input     提交时保存的（已脱敏）分析输入
 *  result    完成后的分析结果
 *  error     失败原因
 *
 * run() 是任务实际执行逻辑，由后台 worker（bin/worker.php）与同步兜底共同复用。
 */
class GameJob
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public static function dir(): string
    {
        $dir = (string) (Config::get('game_analysis.jobs_dir', __DIR__ . '/../../storage/jobs'));
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    public static function path(string $id): string
    {
        return self::dir() . '/' . preg_replace('/[^A-Za-z0-9_\-]/', '', $id) . '.json';
    }

    public static function create(array $input): string
    {
        $id = bin2hex(random_bytes(12));
        $now = time();
        $job = [
            'id' => $id,
            'status' => self::STATUS_PENDING,
            'progress' => 0,
            'stage' => 'queued',
            'stageText' => '已提交，排队中…',
            'createdAt' => $now,
            'updatedAt' => $now,
            'input' => $input,
            'error' => null,
            'result' => null,
        ];
        self::write($id, $job);
        return $id;
    }

    public static function get(string $id): ?array
    {
        $p = self::path($id);
        if (!is_file($p)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($p), true);
        return is_array($data) ? $data : null;
    }

    public static function write(string $id, array $job): void
    {
        $job['updatedAt'] = time();
        file_put_contents(self::path($id), json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public static function update(string $id, array $patch): void
    {
        $job = self::get($id);
        if ($job === null) {
            return;
        }
        foreach ($patch as $k => $v) {
            $job[$k] = $v;
        }
        self::write($id, $job);
    }

    public static function setProgress(string $id, int $pct, string $stage, string $text): void
    {
        self::update($id, [
            'status' => self::STATUS_PROCESSING,
            'progress' => max(0, min(100, $pct)),
            'stage' => $stage,
            'stageText' => $text,
        ]);
    }

    public static function complete(string $id, array $result): void
    {
        self::update($id, [
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'stage' => 'done',
            'stageText' => '分析完成',
            'result' => $result,
        ]);
    }

    public static function fail(string $id, string $error): void
    {
        self::update($id, [
            'status' => self::STATUS_FAILED,
            'stage' => 'failed',
            'stageText' => '分析失败',
            'error' => $error,
        ]);
    }

    /**
     * 执行任务（被 worker 与同步兜底复用）。
     * 通过 $onProgress 回调把进度写回任务文件，便于前端轮询展示。
     */
    public static function run(string $jobId): void
    {
        $job = self::get($jobId);
        if ($job === null) {
            return;
        }
        // 脱离请求生命周期：worker 在独立进程运行，即便父请求结束也不中断。
        set_time_limit(0);
        ignore_user_abort(true);

        self::update($jobId, ['status' => self::STATUS_PROCESSING, 'startedAt' => time()]);
        $input = $job['input'] ?? [];

        try {
            $result = GameAnalyzer::analyze($input, function (string $stage, int $pct, string $text) use ($jobId) {
                self::setProgress($jobId, $pct, $stage, $text);
            });
            self::complete($jobId, $result);
        } catch (\Throwable $e) {
            Logger::exception($e, ['job' => $jobId]);
            self::fail($jobId, $e->getMessage());
        } finally {
            // 清理用户上传的临时视频文件
            if (!empty($input['file']) && is_file($input['file'])) {
                @unlink($input['file']);
            }
        }
    }
}
