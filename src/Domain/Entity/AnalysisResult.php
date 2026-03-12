<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class AnalysisResult
{
    public function __construct(
        private string $content,
        private string $model,
        private int $tokensUsed,
        private float $latencyMs,
        private bool $cached = false,
    ) {
    }

    public function content(): string
    {
        return $this->content;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function tokensUsed(): int
    {
        return $this->tokensUsed;
    }

    public function latencyMs(): float
    {
        return $this->latencyMs;
    }

    public function isCached(): bool
    {
        return $this->cached;
    }

    public function withCached(bool $cached): self
    {
        return new self($this->content, $this->model, $this->tokensUsed, $this->latencyMs, $cached);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'model' => $this->model,
            'tokens_used' => $this->tokensUsed,
            'latency_ms' => $this->latencyMs,
            'cached' => $this->cached,
        ];
    }
}
