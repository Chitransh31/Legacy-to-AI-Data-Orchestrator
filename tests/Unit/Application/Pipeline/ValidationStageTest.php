<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Pipeline;

use App\Application\Pipeline\Stage\ValidationStage;
use App\Domain\Exception\DataValidationException;
use PHPUnit\Framework\TestCase;

final class ValidationStageTest extends TestCase
{
    public function testPassesWithAllRequiredFields(): void
    {
        $stage = new ValidationStage(['ORD_ID', 'CUST_NM']);
        $input = [['ORD_ID' => 'ORD-001', 'CUST_NM' => 'Acme', 'EXTRA' => 'data']];

        $result = $stage->transform($input);
        $this->assertSame('ORD-001', $result[0]['ORD_ID']);
    }

    public function testThrowsOnMissingRequiredField(): void
    {
        $stage = new ValidationStage(['ORD_ID', 'CUST_NM']);
        $input = [['ORD_ID' => 'ORD-001']];

        $this->expectException(DataValidationException::class);
        $stage->transform($input);
    }

    public function testThrowsOnEmptyRequiredField(): void
    {
        $stage = new ValidationStage(['ORD_ID']);
        $input = [['ORD_ID' => '']];

        $this->expectException(DataValidationException::class);
        $stage->transform($input);
    }

    public function testCoercesIntType(): void
    {
        $stage = new ValidationStage([], ['QTY' => 'int']);
        $input = [['QTY' => '50']];

        $result = $stage->transform($input);
        $this->assertSame(50, $result[0]['QTY']);
    }

    public function testCoercesFloatType(): void
    {
        $stage = new ValidationStage([], ['TOTAL' => 'float']);
        $input = [['TOTAL' => '15750.00']];

        $result = $stage->transform($input);
        $this->assertSame(15750.0, $result[0]['TOTAL']);
    }

    public function testNormalizesDateFormats(): void
    {
        $stage = new ValidationStage([], ['DT' => 'date']);

        // US format
        $result = $stage->transform([['DT' => '01/15/2024']]);
        $this->assertSame('2024-01-15', $result[0]['DT']);

        // Slash format
        $result = $stage->transform([['DT' => '2024/04/05']]);
        $this->assertSame('2024-04-05', $result[0]['DT']);

        // ISO format (passthrough)
        $result = $stage->transform([['DT' => '2024-01-15']]);
        $this->assertSame('2024-01-15', $result[0]['DT']);
    }

    public function testValidationErrorContainsFieldDetails(): void
    {
        $stage = new ValidationStage(['ORD_ID', 'CUST_NM']);

        try {
            $stage->transform([['EXTRA' => 'data']]);
            $this->fail('Expected DataValidationException');
        } catch (DataValidationException $e) {
            $errors = $e->fieldErrors();
            $this->assertArrayHasKey('record[0].ORD_ID', $errors);
            $this->assertArrayHasKey('record[0].CUST_NM', $errors);
        }
    }
}
