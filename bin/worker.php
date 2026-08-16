#!/usr/bin/env php
<?php

/**
 * 口播稿异步分析 Worker
 *
 * 由 game-analysis 接口在创建任务后后台拉起（setsid 脱离 php-fpm 请求进程组），
 * 通过 GameJob 把进度/结果写入 storage/jobs/{id}.json，前端轮询 game-job 接口读取。
 *
 * 用法：php bin/worker.php {jobId}
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\GameJob;

$jobId = (string) ($argv[1] ?? '');
if ($jobId === '' || GameJob::get($jobId) === null) {
    fwrite(STDERR, "invalid or unknown job id\n");
    exit(1);
}

// 即便父进程（php-fpm）已结束，也独立把整段分析跑完。
set_time_limit(0);
ignore_user_abort(true);

GameJob::run($jobId);

exit(0);
