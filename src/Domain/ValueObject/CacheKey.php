<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class CacheKey
{
    private string $value;

    public function __construct(string $prefix, string ...$parts)
    {
        if ($prefix === '') {
            throw new InvalidArgumentException('CacheKey prefix cannot be empty.');
        }

        $segments = array_merge([$prefix], $parts);
        $this->value = implode(':', array_map('trim', $segments));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
