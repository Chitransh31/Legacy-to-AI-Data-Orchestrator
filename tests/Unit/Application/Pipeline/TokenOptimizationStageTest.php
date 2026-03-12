<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Pipeline;

use App\Application\Pipeline\Stage\TokenOptimizationStage;
use App\Domain\ValueObject\TokenBudget;
use PHPUnit\Framework\TestCase;

final class TokenOptimizationStageTest extends TestCase
{
    public function testTruncatesOversizedFields(): void
    {
        $stage = new TokenOptimizationStage(
            maxLengths: ['desc' => 20],
        );

        $input = [['desc' => 'This is a very long product description that exceeds the limit']];
        $result = $stage->transform($input);

        $this->assertSame('This is a very long ...', $result[0]['desc']);
    }

    public function testAppliesAbbreviations(): void
    {
        $stage = new TokenOptimizationStage(
            abbreviations: ['California' => 'CA', 'New York' => 'NY'],
        );

        $input = [['state' => 'California', 'city' => 'New York']];
        $result = $stage->transform($input);

        $this->assertSame('CA', $result[0]['state']);
        $this->assertSame('NY', $result[0]['city']);
    }

    public function testKeepsOnlyImportantFieldsWhenOverBudget(): void
    {
        $stage = new TokenOptimizationStage(
            importantFields: ['name', 'total'],
            budget: new TokenBudget(10), // Very small budget
        );

        $input = [['name' => 'Acme', 'total' => 100, 'desc' => 'Some long description', 'extra' => 'data']];
        $result = $stage->transform($input);

        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('total', $result[0]);
        $this->assertArrayNotHasKey('desc', $result[0]);
        $this->assertArrayNotHasKey('extra', $result[0]);
    }

    public function testPreservesAllFieldsWhenUnderBudget(): void
    {
        $stage = new TokenOptimizationStage(
            importantFields: ['name'],
            budget: new TokenBudget(10000),
        );

        $input = [['name' => 'Acme', 'total' => 100]];
        $result = $stage->transform($input);

        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('total', $result[0]);
    }

    public function testPreservesNonStringValues(): void
    {
        $stage = new TokenOptimizationStage(maxLengths: ['name' => 50]);

        $input = [['name' => 'Acme', 'qty' => 50, 'price' => 99.99]];
        $result = $stage->transform($input);

        $this->assertSame(50, $result[0]['qty']);
        $this->assertSame(99.99, $result[0]['price']);
    }
}
