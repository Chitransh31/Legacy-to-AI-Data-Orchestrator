<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\DataSourceId;
use DateTimeImmutable;

final class LegacyRecord
{
    /** @param array<string, mixed> $columns */
    public function __construct(
        private DataSourceId $sourceId,
        private array $columns,
        private DateTimeImmutable $fetchedAt = new DateTimeImmutable(),
    ) {
    }

    public function sourceId(): DataSourceId
    {
        return $this->sourceId;
    }

    /** @return array<string, mixed> */
    public function columns(): array
    {
        return $this->columns;
    }

    public function fetchedAt(): DateTimeImmutable
    {
        return $this->fetchedAt;
    }

    public function isEmpty(): bool
    {
        return $this->columns === [];
    }

    public function get(string $column, mixed $default = null): mixed
    {
        return $this->columns[$column] ?? $default;
    }
}
