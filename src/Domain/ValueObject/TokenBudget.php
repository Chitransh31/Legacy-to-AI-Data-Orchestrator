<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class TokenBudget
{
    private int $maxTokens;

    public function __construct(int $maxTokens)
    {
        if ($maxTokens <= 0) {
            throw new InvalidArgumentException('TokenBudget must be positive.');
        }
        $this->maxTokens = $maxTokens;
    }

    public function maxTokens(): int
    {
        return $this->maxTokens;
    }

    public function fits(int $estimatedTokens): bool
    {
        return $estimatedTokens <= $this->maxTokens;
    }

    public static function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }
}
