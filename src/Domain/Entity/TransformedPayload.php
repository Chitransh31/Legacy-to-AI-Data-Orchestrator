<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class TransformedPayload
{
    /**
     * @param array<int, array<string, mixed>> $records
     */
    public function __construct(
        private array $records,
        private int $estimatedTokens,
        private string $schemaVersion = '1.0',
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function records(): array
    {
        return $this->records;
    }

    public function estimatedTokens(): int
    {
        return $this->estimatedTokens;
    }

    public function schemaVersion(): string
    {
        return $this->schemaVersion;
    }

    public function toJson(): string
    {
        return (string) json_encode($this->records, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function recordCount(): int
    {
        return count($this->records);
    }
}
