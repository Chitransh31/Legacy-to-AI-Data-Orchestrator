<?php

declare(strict_types=1);

namespace App\Domain\Contract;

interface TransformerInterface
{
    /**
     * Transform an array of records through this stage.
     *
     * @param array<int, array<string, mixed>> $records
     * @return array<int, array<string, mixed>>
     */
    public function transform(array $records): array;
}
