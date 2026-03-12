<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Pipeline;

use App\Application\Pipeline\Stage\CleaningStage;
use PHPUnit\Framework\TestCase;

final class CleaningStageTest extends TestCase
{
    private CleaningStage $stage;

    protected function setUp(): void
    {
        $this->stage = new CleaningStage();
    }

    public function testRemovesNullValues(): void
    {
        $input = [['name' => 'Acme', 'email' => null, 'phone' => '555-0101']];
        $result = $this->stage->transform($input);

        $this->assertArrayNotHasKey('email', $result[0]);
        $this->assertSame('Acme', $result[0]['name']);
    }

    public function testTrimsWhitespace(): void
    {
        $input = [['name' => '  Acme Corporation  ', 'city' => '  New York  ']];
        $result = $this->stage->transform($input);

        $this->assertSame('Acme Corporation', $result[0]['name']);
        $this->assertSame('New York', $result[0]['city']);
    }

    public function testCollapsesMultipleSpaces(): void
    {
        $input = [['desc' => 'Widget  Assembly   Kit']];
        $result = $this->stage->transform($input);

        $this->assertSame('Widget Assembly Kit', $result[0]['desc']);
    }

    public function testRemovesEmptyStringsAfterCleaning(): void
    {
        $input = [['name' => 'Acme', 'empty' => '', 'spaces' => '   ']];
        $result = $this->stage->transform($input);

        $this->assertArrayNotHasKey('empty', $result[0]);
        $this->assertArrayNotHasKey('spaces', $result[0]);
    }

    public function testPreservesNumericValues(): void
    {
        $input = [['name' => 'Acme', 'total' => 150.50, 'qty' => 10]];
        $result = $this->stage->transform($input);

        $this->assertSame(150.50, $result[0]['total']);
        $this->assertSame(10, $result[0]['qty']);
    }

    public function testHandlesMultipleRecords(): void
    {
        $input = [
            ['name' => '  Acme  ', 'email' => null],
            ['name' => 'TechStart', 'email' => 'info@tech.com'],
        ];
        $result = $this->stage->transform($input);

        $this->assertCount(2, $result);
        $this->assertSame('Acme', $result[0]['name']);
        $this->assertSame('TechStart', $result[1]['name']);
    }
}
