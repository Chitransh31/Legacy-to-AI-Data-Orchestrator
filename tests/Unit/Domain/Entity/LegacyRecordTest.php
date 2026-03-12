<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\LegacyRecord;
use App\Domain\ValueObject\DataSourceId;
use PHPUnit\Framework\TestCase;

final class LegacyRecordTest extends TestCase
{
    public function testCreatesWithColumnsAndSource(): void
    {
        $source = new DataSourceId('erp_orders');
        $columns = ['ORD_ID' => 'ORD-001', 'CUST_NM' => 'Acme'];
        $record = new LegacyRecord($source, $columns);

        $this->assertTrue($source->equals($record->sourceId()));
        $this->assertSame($columns, $record->columns());
        $this->assertFalse($record->isEmpty());
    }

    public function testEmptyRecordDetection(): void
    {
        $record = new LegacyRecord(new DataSourceId('test_source'), []);
        $this->assertTrue($record->isEmpty());
    }

    public function testGetColumnValue(): void
    {
        $record = new LegacyRecord(
            new DataSourceId('erp_orders'),
            ['ORD_ID' => 'ORD-001', 'CUST_NM' => 'Acme']
        );

        $this->assertSame('ORD-001', $record->get('ORD_ID'));
        $this->assertSame('Acme', $record->get('CUST_NM'));
        $this->assertNull($record->get('MISSING'));
        $this->assertSame('default', $record->get('MISSING', 'default'));
    }
}
