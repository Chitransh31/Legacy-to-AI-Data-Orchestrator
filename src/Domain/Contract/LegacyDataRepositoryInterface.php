<?php

declare(strict_types=1);

namespace App\Domain\Contract;

use App\Domain\Entity\LegacyRecord;
use App\Domain\ValueObject\DataSourceId;

interface LegacyDataRepositoryInterface
{
    public function fetchById(DataSourceId $source, string $id): LegacyRecord;

    /**
     * @param array<string, mixed> $criteria
     * @return LegacyRecord[]
     */
    public function fetchBatch(DataSourceId $source, array $criteria): array;
}
