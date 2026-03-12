<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\TokenBudget;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TokenBudgetTest extends TestCase
{
    public function testCreatesWithPositiveTokens(): void
    {
        $budget = new TokenBudget(4096);
        $this->assertSame(4096, $budget->maxTokens());
    }

    public function testRejectsZeroTokens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TokenBudget(0);
    }

    public function testRejectsNegativeTokens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TokenBudget(-1);
    }

    public function testFitsReturnsTrueWhenUnderBudget(): void
    {
        $budget = new TokenBudget(1000);
        $this->assertTrue($budget->fits(500));
        $this->assertTrue($budget->fits(1000));
    }

    public function testFitsReturnsFalseWhenOverBudget(): void
    {
        $budget = new TokenBudget(1000);
        $this->assertFalse($budget->fits(1001));
    }

    public function testEstimateTokensRoughlyDividesByFour(): void
    {
        $this->assertSame(3, TokenBudget::estimateTokens('Hello World!'));
        $this->assertSame(1, TokenBudget::estimateTokens('Hi'));
        $this->assertSame(0, TokenBudget::estimateTokens(''));
    }
}
