<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\DataSourceId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DataSourceIdTest extends TestCase
{
    public function testCreatesValidId(): void
    {
        $id = new DataSourceId('erp_orders');
        $this->assertSame('erp_orders', $id->value());
        $this->assertSame('erp_orders', (string) $id);
    }

    public function testTrimsWhitespace(): void
    {
        $id = new DataSourceId('  erp_orders  ');
        $this->assertSame('erp_orders', $id->value());
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DataSourceId('');
    }

    public function testRejectsWhitespaceOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DataSourceId('   ');
    }

    public function testRejectsUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DataSourceId('ERP_Orders');
    }

    public function testRejectsSpecialCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DataSourceId('erp-orders');
    }

    public function testRejectsStartingWithNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DataSourceId('1erp_orders');
    }

    public function testEquality(): void
    {
        $a = new DataSourceId('erp_orders');
        $b = new DataSourceId('erp_orders');
        $c = new DataSourceId('crm_contacts');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
