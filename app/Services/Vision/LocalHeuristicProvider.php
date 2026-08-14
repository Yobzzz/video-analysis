<?php

namespace App\Services\Vision;

use App\Utils\Config;

/**
 * 离线启发式 Provider（无视觉模型）
 *
 * 仅凭“画面变化”（ffmpeg 场景切换检测）+ 抽帧时间点 + 视频标题/作者元信息，
 * 推断操作 / 陷阱 / 翻车 / 通关 的事件分段，并沿时间轴梳理出一份
 * “基于视频内容推进”的口播稿——每一段都锚定具体时间点，避免与内容无关的套话。
 *
 * 这是本地可跑的默认实现：无需任何 API Key、不联网。
 * 当配置了 OpenAI 视觉 Key 时，由 OpenAIVisionProvider 接管真实理解。
 */
class LocalHeuristicProvider implements VisionProviderInterface
{
    private const TYPE_LABEL = [
        'operation' => '操作',
        'trap'      => '陷阱',
        'fail'      => '翻车',
        'clear'     => '通关',
    ];

    public function name(): string
    {
        return 'heuristic';
    }

    public function analyze(string $videoFile, array $meta): array
    {
        $duration = (float) ($meta['duration'] ?? 0);
        $scenes = $meta['scenes'] ?? [];
        $frames = $meta['frames'] ?? [];
        $title = trim((string) ($meta['title'] ?? ''));
        $author = trim((string) ($meta['author'] ?? ''));

        // 分段边界：0 → 场景切换点 → 抽帧时间点 → 时长（合并去重排序）
        $bounds = [0.0];
        foreach ($scenes as $s) {
            $bounds[] = (float) $s['t'];
        }
        foreach ($frames as $f) {
            $bounds[] = (float) ($f['t'] ?? 0);
        }
        $bounds[] = $duration > 0 ? $duration : (count($bounds) > 1 ? max($bounds) + 2 : 10);
        $bounds = array_values(array_unique(array_map(static fn($v) => round((float) $v, 1), $bounds)));
        sort($bounds);

        $segments = [];
        $n = count($bounds) - 1;
        for ($i = 0; $i < $n; $i++) {
            $start = $bounds[$i];
            $end = $bounds[$i + 1];
            if ($end - $start < 0.2) {
                continue;
            }
            $segScore = $this->scoreInRange($scenes, $start, $end);
            $type = $this->inferType($i, $n, $segScore, ($end - $start), $duration);
            $segments[] = [
                't_start' => $start,
                't_end'   => $end,
                'type'    => $type,
                'label'   => self::TYPE_LABEL[$type],
                'score'   => round($segScore, 3),
                'desc'    => $this->describe($type, $start, $end, $i, $n),
            ];
        }

        if (empty($segments)) {
            $segments[] = [
                't_start' => 0.0, 't_end' => round($duration, 1),
                'type' => 'operation', 'label' => '操作', 'score' => 0.0,
                'desc' => '整段画面都在推进，节奏平稳。',
            ];
        }

        $summary = $this->summarize($title, $author, $duration, $segments);
        $script = $this->buildScript($title, $duration, $segments);

        return [
            'segments' => $segments,
            'summary'  => $summary,
            'script'   => $script,
            'provider' => $this->name(),
        ];
    }

    private function scoreInRange(array $scenes, float $start, float $end): float
    {
        $max = 0.0;
        foreach ($scenes as $s) {
            $t = (float) $s['t'];
            if ($t >= $start && $t < $end) {
                $max = max($max, (float) ($s['score'] ?? 0));
            }
        }
        return $max;
    }

    private function inferType(int $i, int $n, float $score, float $len, float $duration): string
    {
        if ($i === 0) {
            return 'operation';
        }
        if ($i === $n - 1) {
            // 末段较长视为收尾/通关，否则仍是操作收尾
            return ($duration > 0 && $len >= $duration * 0.16) ? 'clear' : 'operation';
        }
        if ($score >= 0.5) {
            return 'fail';   // 剧烈变化 → 翻车
        }
        if ($score >= 0.38) {
            return 'trap';   // 中等变化 → 陷阱
        }
        // 常规段按位置交替，避免全是“操作”显得单调
        return ($i % 2 === 0) ? 'operation' : 'trap';
    }

    /**
     * 每一段的描述都锚定具体时间点，与画面时间轴绑定
     */
    private function describe(string $type, float $start, float $end, int $i, int $n): string
    {
        $pos = $this->positionWord($start, $end, $i, $n);
        return match ($type) {
            'trap'  => "{$pos}画面出现明显变化，疑似埋了陷阱。",
            'fail'  => "{$pos}画面剧烈切换，看起来翻车了。",
            'clear' => "最后一段节奏放缓，收尾结束。",
            default => "{$pos}画面持续推进，操作没断。",
        };
    }

    /**
     * 把时间段转成口语化的画面位置描述
     */
    private function positionWord(float $start, float $end, int $i, int $n): string
    {
        if ($i === 0) {
            return '开场第 ' . round($start, 0) . ' 秒左右，';
        }
        if ($i === $n - 1) {
            return '临近结尾（约 ' . round($start, 0) . ' 秒后），';
        }
        return '大约 ' . round($start, 0) . ' 秒处，';
    }

    private function summarize(string $title, string $author, float $duration, array $segments): string
    {
        $counts = ['operation' => 0, 'trap' => 0, 'fail' => 0, 'clear' => 0];
        foreach ($segments as $s) {
            $counts[$s['type']]++;
        }
        $parts = [];
        if ($counts['trap']) {
            $parts[] = $counts['trap'] . '个陷阱';
        }
        if ($counts['fail']) {
            $parts[] = $counts['fail'] . '次翻车';
        }
        if ($counts['clear']) {
            $parts[] = '收尾完成';
        }
        if (empty($parts)) {
            $parts[] = '全程推进';
        }
        $head = $title !== '' ? "「{$title}」" : '一段短视频';
        $who = $author !== '' ? "（作者：{$author}）" : '';
        return $head . $who . '，时长约 ' . round($duration, 1) . ' 秒，' . implode('、', $parts) . '。';
    }

    /**
     * 沿时间轴梳理画面流程，生成口播播音稿
     *
     * 结构与短视频口播一致：开场交代主题 → 按时间推进描述过程 → 收尾互动。
     * 每一句都锚定画面时间段，与视频内容强相关。
     */
    private function buildScript(string $title, float $duration, array $segments): string
    {
        $lines = [];

        // 开场
        if ($title !== '') {
            $lines[] = '今天看的这段视频，标题叫「' . $title . '」。';
        } else {
            $lines[] = '今天看的这段短视频，全程大约' . round($duration, 1) . '秒。';
        }
        $lines[] = '我按画面推进的顺序，给你捋一遍。';

        // 主体：沿时间轴逐段叙述
        foreach ($segments as $i => $s) {
            $lines[] = $s['desc'];
            if ($s['type'] === 'trap') {
                $lines[] = '这里多半是策划留的坑，注意别踩。';
            } elseif ($s['type'] === 'fail') {
                $lines[] = '这一下没接住，后面应该还有反转。';
            } elseif ($s['type'] === 'clear') {
                $lines[] = '到这里画面收住了，结尾挺利落。';
            } elseif ($i === 0) {
                $lines[] = '先别急着下结论，接着往下看。';
            }
        }

        // 收尾互动
        $lines[] = '整段看下来，节奏有起有伏，画面变化了' . count($segments) . '个阶段。';
        $lines[] = '你觉得这段视频到底在讲什么？评论区聊聊。';
        $lines[] = '点个关注，下一条视频我继续给你拆。';

        return implode("\n", $lines);
    }
}
