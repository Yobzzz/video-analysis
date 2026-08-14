<?php

namespace App\Services;

/**
 * 小游戏口播配音稿生成器
 *
 * 把结构化分析结果（操作 / 陷阱 / 翻车 / 通关 分段）转化为适合 AI 朗读的短句配音稿：
 *  - 描述操作全过程
 *  - 吐槽关卡
 *  - 制造悬念
 *  - 短句、口语化、避免歧义标点（适合 TTS）
 *  - 结尾带上互动提问，引导观众试玩
 */
class GameNarrationGenerator
{
    /**
     * @param array $analysis ['segments'=>[{type,label,desc,t_start,t_end}], 'summary'=>string]
     * @param array $meta     ['duration'=>float]
     * @return array{ title: string, hook: string, lines: string[], script: string, tags: string[] }
     */
    public static function generate(array $analysis, array $meta = []): array
    {
        $segments = $analysis['segments'] ?? [];
        $summary = $analysis['summary'] ?? '';

        $counts = ['operation' => 0, 'trap' => 0, 'fail' => 0, 'clear' => 0];
        foreach ($segments as $s) {
            $counts[$s['type']]++;
        }

        $title = self::title($counts);
        $hook = self::hook($counts);

        $lines = [];
        $lines[] = $hook;

        $suspense = false;
        foreach ($segments as $i => $s) {
            $lines = array_merge($lines, self::segmentLines($s, $i, $counts, $suspense));
            if ($s['type'] === 'fail') {
                $suspense = true; // 翻车后制造“后面更离谱”的悬念
            }
        }

        $lines = array_merge($lines, self::ending($counts, $summary));

        $script = implode("\n", $lines);

        return [
            'title' => $title,
            'hook'  => $hook,
            'lines' => $lines,
            'script' => $script,
            'tags'  => self::tags($counts),
        ];
    }

    private static function title(array $counts): string
    {
        if ($counts['fail'] >= 2) {
            return '这关我替你翻车了，劝你别头铁';
        }
        if ($counts['trap'] >= 2) {
            return '策划的脑洞，我算是领教了';
        }
        if ($counts['clear'] > 0) {
            return '这游戏我帮你打通关，体验拉满';
        }
        return '一段小游戏实录，坑和笑点都有';
    }

    private static function hook(array $counts): string
    {
        if ($counts['trap'] || $counts['fail']) {
            return '先说好，这视频有点上头，也有点欺负人。';
        }
        return '先说好，这视频有点上头。';
    }

    /**
     * 单段事件 → 1~2 句短口语
     */
    private static function segmentLines(array $s, int $i, array $counts, bool $suspense): array
    {
        $desc = trim((string) ($s['desc'] ?? ''));
        $out = [];

        switch ($s['type']) {
            case 'operation':
                $out[] = $desc ?: '继续往前推，节奏没断。';
                break;
            case 'trap':
                $out[] = $desc ?: '这关埋了个坑，你细品。';
                $out[] = '我是真没绷住。';
                break;
            case 'fail':
                $out[] = $desc ?: '好家伙，直接翻车。';
                $out[] = '但你猜怎么着？后面更离谱。';
                break;
            case 'clear':
                $out[] = $desc ?: '终于通关，爽到。';
                $out[] = '这感觉，谁玩谁知道。';
                break;
            default:
                $out[] = $desc;
        }

        // 在“通关前最后一波”制造悬念钩子
        if ($suspense && $s['type'] === 'clear') {
            array_unshift($out, '就在我以为要寄的时候——');
        }

        return array_values(array_filter($out, static fn($l) => $l !== ''));
    }

    private static function ending(array $counts, string $summary): array
    {
        $lines = [];
        if ($counts['clear'] > 0) {
            $lines[] = '通关那一刻，值了。';
        } else {
            $lines[] = '这关我还没过，先留个悬念。';
        }
        $lines[] = '你敢不敢也来一把？';
        $lines[] = '评论区告诉我，你能过几关。';
        $lines[] = '点开试玩，看看是你手稳，还是策划心狠。';
        return $lines;
    }

    private static function tags(array $counts): array
    {
        $tags = ['小游戏', '实况', '口播稿'];
        if ($counts['fail']) {
            $tags[] = '翻车';
        }
        if ($counts['trap']) {
            $tags[] = '陷阱';
        }
        if ($counts['clear']) {
            $tags[] = '通关';
        }
        return $tags;
    }
}
