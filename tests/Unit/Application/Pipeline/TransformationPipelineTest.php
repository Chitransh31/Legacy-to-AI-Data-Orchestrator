<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Pipeline;

use App\Application\Pipeline\Stage\CleaningStage;
use App\Application\Pipeline\Stage\SchemaMapStage;
use App\Application\Pipeline\Stage\TokenOptimizationStage;
use App\Application\Pipeline\Stage\ValidationStage;
use App\Application\Pipeline\TransformationPipeline;
use PHPUnit\Framework\TestCase;

final class TransformationPipelineTest extends TestCase
{
    public function testFullPipelineTransformsData(): void
    {
        $pipeline = new TransformationPipeline(
            new CleaningStage(),
            new ValidationStage(['ORD_ID', 'CUST_NM'], ['QTY' => 'int', 'ORD_TOT' => 'float', 'ORD_DT' => 'date']),
            new SchemaMapStage(
                ['ORD_ID' => 'order_id', 'CUST_NM' => 'customer_name', 'ORD_TOT' => 'order_total', 'ORD_DT' => 'order_date', 'QTY' => 'quantity'],
                ['INTERNAL_ID', 'AUDIT_TS', 'MODIFIED_BY']
            ),
            new TokenOptimizationStage(maxLengths: ['customer_name' => 100]),
        );

        $rawData = [
            [
                'ORD_ID' => 'ORD-001',
                'CUST_NM' => '  Acme Corporation  ',
                'ORD_DT' => '01/15/2024',
                'ORD_TOT' => '15750.00',
                'QTY' => '50',
                'INTERNAL_ID' => 1,
                'AUDIT_TS' => '2024-01-15',
                'MODIFIED_BY' => 'SYSTEM',
            ],
        ];

        $result = $pipeline->process($rawData);

        $this->assertSame(1, $result->recordCount());
        $this->assertGreaterThan(0, $result->estimatedTokens());

        $record = $result->records()[0];
        $this->assertSame('ORD-001', $record['order_id']);
        $this->assertSame('Acme Corporation', $record['customer_name']);
        $this->assertSame(15750.0, $record['order_total']);
        $this->assertSame(50, $record['quantity']);
        $this->assertSame('2024-01-15', $record['order_date']);

        // Excluded columns should not be present
        $this->assertArrayNotHasKey('INTERNAL_ID', $record);
        $this->assertArrayNotHasKey('AUDIT_TS', $record);
        $this->assertArrayNotHasKey('MODIFIED_BY', $record);
    }

    public function testEmptyDataProducesEmptyPayload(): void
    {
        $pipeline = new TransformationPipeline(new CleaningStage());
        $result = $pipeline->process([]);

        $this->assertSame(0, $result->recordCount());
        $this->assertSame([], $result->records());
    }
}
