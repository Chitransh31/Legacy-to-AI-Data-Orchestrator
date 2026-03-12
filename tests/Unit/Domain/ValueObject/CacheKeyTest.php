<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\CacheKey;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CacheKeyTest extends TestCase
{
    public function testCreatesKeyWithPrefixAndParts(): void
    {
        $key = new CacheKey('transform', 'erp_orders', 'abc123', '1.0');
        $this->assertSame('transform:erp_orders:abc123:1.0', $key->value());
        $this->assertSame('transform:erp_orders:abc123:1.0', (string) $key);
    }

    public function testCreatesKeyWithPrefixOnly(): void
    {
        $key = new CacheKey('health');
        $this->assertSame('health', $key->value());
    }

    public function testRejectsEmptyPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CacheKey('');
    }

    public function testDeterministicKeys(): void
    {
        $a = new CacheKey('llm', 'hash1', 'hash2');
        $b = new CacheKey('llm', 'hash1', 'hash2');
        $this->assertSame($a->value(), $b->value());
    }
}
