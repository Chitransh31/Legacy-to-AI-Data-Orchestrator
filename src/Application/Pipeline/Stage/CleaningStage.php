<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Stage;

use App\Domain\Contract\TransformerInterface;

final class CleaningStage implements TransformerInterface
{
    public function transform(array $records): array
    {
        return array_map([$this, 'cleanRecord'], $records);
    }

    /** @param array<string, mixed> $record */
    private function cleanRecord(array $record): array
    {
        $cleaned = [];

        foreach ($record as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_string($value)) {
                $value = $this->cleanString($value);
                if ($value === '') {
                    continue;
                }
            }

            $cleaned[$key] = $value;
        }

        return $cleaned;
    }

    private function cleanString(string $value): string
    {
        // Strip control characters except newline and tab
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? $value;

        // Normalize to UTF-8
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }

        // Collapse multiple whitespace
        $value = (string) preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}
