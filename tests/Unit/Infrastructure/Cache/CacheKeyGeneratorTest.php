<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Cache;

use App\Domain\ValueObject\DataSourceId;
use App\Infrastructure\Cache\CacheKeyGenerator;
use PHPUnit\Framework\TestCase;

final class CacheKeyGeneratorTest extends TestCase
{
    private CacheKeyGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new CacheKeyGenerator();
    }

    public function testTransformKeyIsDeterministic(): void
    {
        $source = new DataSourceId('erp_orders');
        $query = ['status' => 'SHIPPED'];

        $key1 = $this->generator->forTransform($source, $query);
        $key2 = $this->generator->forTransform($source, $query);

        $this->assertSame($key1->value(), $key2->value());
        $this->assertStringStartsWith('transform:erp_orders:', $key1->value());
    }

    public function testDifferentQueriesProduceDifferentKeys(): void
    {
        $source = new DataSourceId('erp_orders');

        $key1 = $this->generator->forTransform($source, ['status' => 'SHIPPED']);
        $key2 = $this->generator->forTransform($source, ['status' => 'PENDING']);

        $this->assertNotSame($key1->value(), $key2->value());
    }

    public function testLlmKeyIsDeterministic(): void
    {
        $key1 = $this->generator->forLlmResponse('{"data":"test"}', 'Analyze this', 'gpt-4o');
        $key2 = $this->generator->forLlmResponse('{"data":"test"}', 'Analyze this', 'gpt-4o');

        $this->assertSame($key1->value(), $key2->value());
        $this->assertStringStartsWith('llm:', $key1->value());
    }

    public function testSourcePrefixFormat(): void
    {
        $source = new DataSourceId('erp_orders');
        $prefix = $this->generator->sourcePrefix($source);

        $this->assertSame('transform:erp_orders', $prefix);
    }
}
