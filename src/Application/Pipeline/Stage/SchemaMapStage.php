<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Stage;

use App\Domain\Contract\TransformerInterface;

final class SchemaMapStage implements TransformerInterface
{
    /**
     * @param array<string, string> $columnMap  old_name => new_name
     * @param string[] $excludeColumns  columns to remove
     */
    public function __construct(
        private array $columnMap = [],
        private array $excludeColumns = [],
    ) {
    }

    public function transform(array $records): array
    {
        return array_map([$this, 'mapRecord'], $records);
    }

    /** @param array<string, mixed> $record */
    private function mapRecord(array $record): array
    {
        // Remove excluded columns
        foreach ($this->excludeColumns as $col) {
            unset($record[$col]);
        }

        // Remap column names
        $mapped = [];
        foreach ($record as $key => $value) {
            $newKey = $this->columnMap[$key] ?? $key;
            $mapped[$newKey] = $value;
        }

        return $mapped;
    }
}
