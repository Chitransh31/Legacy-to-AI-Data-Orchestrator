<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class AnalysisResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int $tokensUsed,
        public readonly float $latencyMs,
        public readonly bool $cached,
        public readonly int $recordCount,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'analysis' => $this->content,
            'model' => $this->model,
            'tokens_used' => $this->tokensUsed,
            'latency_ms' => round($this->latencyMs, 2),
            'cached' => $this->cached,
            'record_count' => $this->recordCount,
        ];
    }
}
