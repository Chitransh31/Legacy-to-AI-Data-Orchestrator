<?php

declare(strict_types=1);

namespace App\Domain\Contract;

use App\Domain\ValueObject\CacheKey;

interface CacheInterface
{
    public function get(CacheKey $key): ?string;

    public function set(CacheKey $key, string $value, int $ttl = 3600): void;

    public function has(CacheKey $key): bool;

    public function invalidate(CacheKey $key): void;

    public function invalidateByPrefix(string $prefix): void;
}
