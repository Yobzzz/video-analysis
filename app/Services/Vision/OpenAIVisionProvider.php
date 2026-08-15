<?php

namespace App\Services\Vision;

use App\Utils\Config;
use App\Utils\HttpClient;

/**
 * OpenAI 兼容视觉 Provider（真正"读画面"理解内容）
 *
 * 将抽帧图片以 base64 发给视觉模型，要求其：
 *  1. 逐帧看懂画面里实际发生的内容（玩法步骤、界面文字、情节推进）
 *  2. 梳理成内容剧本（开场 → 主体流程 → 高潮/翻车 → 收尾）
 *  3. 输出可直接用于 AI 配音的播音稿（贴合画面事实，禁止套话）
 *
 * 当配置了 GAME_ANALYSIS_PROVIDER=openai 或 auto（且存在 OPENAI_API_KEY）时启用。
 */
class OpenAIVisionProvider implements VisionProviderInterface
{
    private const ALLOWED = ['operation', 'trap', 'fail', 'clear'];

    /**
     * 用户在前端自定义传入的 API 配置（优先级高于 config/app.php）
     * 结构：['api_key'=>string, 'base_url'=>string, 'model'=>string]
     */
    private array $overrides;

    public function __construct(array $overrides = [])
    {
        $this->overrides = $overrides;
    }

    private const SYSTEM_PROMPT = <<<PROMPT
你是一个短视频内容分析师兼口播稿编剧，负责把一段视频"看懂"并写成播音稿。

我会给你若干按时间顺序的抽帧图片，以及视频的标题、作者、时长。
请你像看完整视频一样，逐帧推断画面里真正发生的内容。

分析步骤（必须按这个顺序思考）：
1. 主题判断：视频在讲什么？类型是小游戏实况、关卡教学、搞笑短剧、生活记录，还是其他？
2. 内容流程：开场讲了什么 → 中间分几步推进（游戏的操作步骤/关卡名/剧情发展）→ 有没有关键节点（高光、翻车、陷阱、通关）→ 结尾如何收场。
3. 画面细节：尽量读画面里的可见信息——标题文字、关卡名、按钮、分数、道具、人物动作、对话气泡。这些要写进稿子。

输出要求：
- script（播音稿）必须基于上述内容流程，具体描述"这视频里发生了什么"，禁止"操作丝滑、画面炫酷、很有感觉"这类与内容无关的套话。
- 短句、口语化、适合 TTS 朗读；结尾带互动提问引导观众参与。
- 只输出 JSON，不要任何解释。格式：
{
  "topic": "一句话主题判断",
  "segments": [
    {"t_start": 数字, "t_end": 数字, "type": "operation|trap|fail|clear", "label": "中文标签", "desc": "该段画面里真实出现的内容，具体到操作/文字/关卡"}
  ],
  "summary": "一段内容总览（讲了这个视频的完整故事）",
  "script": "完整播音稿：开场 → 按内容流程分步叙述 → 高潮/收尾 → 互动提问"
}
PROMPT;

    public function name(): string
    {
        return 'openai';
    }

    public function analyze(string $videoFile, array $meta): array
    {
        $cfg = Config::get('game_analysis.openai', []);
        // 前端用户自定义配置优先于全局配置（允许每个用户用自己的 API Key）
        $apiKey = (string) ($this->overrides['api_key'] ?? $cfg['api_key'] ?? '');
        if ($apiKey === '') {
            throw new \RuntimeException('未配置 API Key，无法使用视觉模型。可在页面上方「AI 视觉模型配置」填入自己的 OpenAI 兼容 API Key。');
        }
        $baseUrl = rtrim((string) ($this->overrides['base_url'] ?? $cfg['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $model = (string) ($this->overrides['model'] ?? $cfg['model'] ?? 'gpt-4o-mini');

        $frames = $meta['frames'] ?? [];
        if (empty($frames)) {
            throw new \RuntimeException('没有可用抽帧，无法调用视觉模型');
        }

        $imageParts = [];
        foreach ($frames as $f) {
            $dataUrl = $f['dataUrl'] ?? null;
            if (!$dataUrl) {
                continue;
            }
            $imageParts[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $dataUrl, 'detail' => 'low'],
            ];
        }
        if (empty($imageParts)) {
            throw new \RuntimeException('抽帧无有效图片数据');
        }

        $userText = '视频时长约 ' . round((float) ($meta['duration'] ?? 0), 1) . ' 秒';
        if (!empty($meta['title'])) {
            $userText .= '，标题「' . $meta['title'] . '」';
        }
        if (!empty($meta['author'])) {
            $userText .= '，作者「' . $meta['author'] . '」';
        }
        $userText .= '。以下是按时间顺序的 ' . count($imageParts) . ' 张抽帧，请逐帧读懂画面内容，按分析步骤输出 JSON。';

        $body = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => array_merge(
                    [['type' => 'text', 'text' => $userText]],
                    $imageParts
                )],
            ],
            'response_format' => ['type' => 'json_object'],
            // 限制输出长度，防止推理类模型（如 doubao-seed-*-pro）长时间思考拖垮请求
            'max_tokens' => 4000,
        ], JSON_UNESCAPED_UNICODE);

        $visionConnectTimeout = (int) Config::get('game_analysis.openai.connect_timeout', 15);
        $visionTimeout = (int) Config::get('game_analysis.openai.timeout', 120);
        $respHeaders = null;
        // 视觉推理（多帧 + 长输出）耗时远高于普通接口；海外服务器连国内 API 延迟也高。
        // 放宽超时并用 maxRetries=0（慢请求不应重试，避免重复扣费/重复等待）。
        $resp = HttpClient::request($baseUrl . '/chat/completions', $body, [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        ], 0, $respHeaders, $visionConnectTimeout, $visionTimeout);

        if (!$resp['success']) {
            throw new \RuntimeException('视觉模型请求失败：' . ($resp['error'] ?: 'HTTP ' . ($resp['http_code'] ?? 0)));
        }

        $json = json_decode($resp['data'], true);
        $content = $json['choices'][0]['message']['content'] ?? '';
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));
        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['segments'])) {
            throw new \RuntimeException('视觉模型返回格式异常');
        }

        $segments = [];
        foreach ($data['segments'] as $s) {
            $type = in_array($s['type'] ?? '', self::ALLOWED, true) ? $s['type'] : 'operation';
            $segments[] = [
                't_start' => (float) ($s['t_start'] ?? 0),
                't_end'   => (float) ($s['t_end'] ?? 0),
                'type'    => $type,
                'label'   => $s['label'] ?? '',
                'score'   => 0.0,
                'desc'    => $s['desc'] ?? '',
            ];
        }

        return [
            'segments' => $segments,
            'summary'  => (string) ($data['summary'] ?? ''),
            'script'   => isset($data['script']) ? (string) $data['script'] : null,
            'provider' => $this->name(),
        ];
    }
}
