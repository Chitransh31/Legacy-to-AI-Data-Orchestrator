<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Pipeline;

use App\Application\Pipeline\Stage\SchemaMapStage;
use PHPUnit\Framework\TestCase;

final class SchemaMapStageTest extends TestCase
{
    public function testRemapsColumnNames(): void
    {
        $stage = new SchemaMapStage([
            'CUST_NM' => 'customer_name',
            'ORD_DT' => 'order_date',
        ]);

        $input = [['CUST_NM' => 'Acme', 'ORD_DT' => '2024-01-15', 'OTHER' => 'data']];
        $result = $stage->transform($input);

        $this->assertSame('Acme', $result[0]['customer_name']);
        $this->assertSame('2024-01-15', $result[0]['order_date']);
        $this->assertSame('data', $result[0]['OTHER']);
        $this->assertArrayNotHasKey('CUST_NM', $result[0]);
    }

    public function testExcludesColumns(): void
    {
        $stage = new SchemaMapStage([], ['INTERNAL_ID', 'AUDIT_TS']);

        $input = [['ORD_ID' => '001', 'INTERNAL_ID' => 1, 'AUDIT_TS' => '2024-01-01']];
        $result = $stage->transform($input);

        $this->assertArrayNotHasKey('INTERNAL_ID', $result[0]);
        $this->assertArrayNotHasKey('AUDIT_TS', $result[0]);
        $this->assertSame('001', $result[0]['ORD_ID']);
    }

    public function testCombinesMapAndExclude(): void
    {
        $stage = new SchemaMapStage(
            ['CUST_NM' => 'customer_name'],
            ['INTERNAL_ID']
        );

        $input = [['CUST_NM' => 'Acme', 'INTERNAL_ID' => 1, 'STATUS' => 'active']];
        $result = $stage->transform($input);

        $this->assertSame('Acme', $result[0]['customer_name']);
        $this->assertSame('active', $result[0]['STATUS']);
        $this->assertArrayNotHasKey('INTERNAL_ID', $result[0]);
        $this->assertArrayNotHasKey('CUST_NM', $result[0]);
    }
}
