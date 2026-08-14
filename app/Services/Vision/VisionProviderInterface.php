<?php

namespace App\Services\Vision;

/**
 * 视觉分析 Provider 接口
 *
 * analyze() 接收视频文件与基础元信息，返回结构化分析结果：
 *  - segments: 事件片段数组，每个含 t_start/t_end/type/label/desc/score
 *  - summary:  一句话总览
 *  - script:   可选，Provider 直接给出的配音稿（为空时由 GameNarrationGenerator 生成）
 *
 * type 取值：operation（操作）/ trap（陷阱）/ fail（翻车）/ clear（通关）
 */
interface VisionProviderInterface
{
    /**
     * @param string $videoFile 本地视频文件路径
     * @param array  $meta      ['duration'=>float, 'scenes'=>[{t,score}], 'frames'=>[{t,path}]]
     * @return array{ segments: array, summary: string, script: ?string, provider: string }
     */
    public function analyze(string $videoFile, array $meta): array;

    public function name(): string;
}
