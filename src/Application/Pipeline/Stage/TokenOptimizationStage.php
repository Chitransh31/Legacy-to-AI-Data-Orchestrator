<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Stage;

use App\Domain\Contract\TransformerInterface;
use App\Domain\ValueObject\TokenBudget;

final class TokenOptimizationStage implements TransformerInterface
{
    /**
     * @param array<string, int> $maxLengths  field => max character length
     * @param string[] $importantFields  fields to keep when trimming for budget
     * @param array<string, string> $abbreviations  verbose => short replacements
     */
    public function __construct(
        private array $maxLengths = [],
        private array $importantFields = [],
        private ?TokenBudget $budget = null,
        private array $abbreviations = [],
    ) {
    }

    public function transform(array $records): array
    {
        $records = array_map([$this, 'optimizeRecord'], $records);

        if ($this->budget !== null) {
            $records = $this->fitToBudget($records);
        }

        return $records;
    }

    /** @param array<string, mixed> $record */
    private function optimizeRecord(array $record): array
    {
        foreach ($record as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            // Apply abbreviations
            foreach ($this->abbreviations as $verbose => $short) {
                $value = str_replace($verbose, $short, $value);
            }

            // Truncate oversized fields
            if (isset($this->maxLengths[$key]) && mb_strlen($value) > $this->maxLengths[$key]) {
                $value = mb_substr($value, 0, $this->maxLengths[$key]) . '...';
            }

            $record[$key] = $value;
        }

        return $record;
    }

    /**
     * @param array<int, array<string, mixed>> $records
     * @return array<int, array<string, mixed>>
     */
    private function fitToBudget(array $records): array
    {
        $json = (string) json_encode($records, JSON_UNESCAPED_UNICODE);
        $estimated = TokenBudget::estimateTokens($json);

        if ($this->budget->fits($estimated)) {
            return $records;
        }

        // Keep only important fields to reduce token count
        if ($this->importantFields !== []) {
            $records = array_map(function (array $record): array {
                return array_filter(
                    $record,
                    fn(string $key) => in_array($key, $this->importantFields, true),
                    ARRAY_FILTER_USE_KEY
                );
            }, $records);
        }

        return $records;
    }
}
