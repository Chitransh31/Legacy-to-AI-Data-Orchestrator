<?php

declare(strict_types=1);

namespace App\Application\Pipeline\Stage;

use App\Domain\Contract\TransformerInterface;
use App\Domain\Exception\DataValidationException;
use DateTimeImmutable;

final class ValidationStage implements TransformerInterface
{
    /**
     * @param string[] $requiredFields
     * @param array<string, string> $typeMap  field => type ('int','float','string','date')
     */
    public function __construct(
        private array $requiredFields = [],
        private array $typeMap = [],
    ) {
    }

    public function transform(array $records): array
    {
        $errors = [];

        foreach ($records as $index => $record) {
            $recordErrors = $this->validateRecord($record, $index);
            if ($recordErrors !== []) {
                $errors = array_merge($errors, $recordErrors);
            }
        }

        if ($errors !== []) {
            throw DataValidationException::withErrors($errors);
        }

        return array_map([$this, 'coerceTypes'], $records);
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, string[]>
     */
    private function validateRecord(array $record, int $index): array
    {
        $errors = [];

        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $record) || $record[$field] === '' || $record[$field] === null) {
                $key = "record[{$index}].{$field}";
                $errors[$key] = ["Required field '{$field}' is missing or empty."];
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $record */
    private function coerceTypes(array $record): array
    {
        foreach ($this->typeMap as $field => $type) {
            if (!array_key_exists($field, $record)) {
                continue;
            }

            $record[$field] = match ($type) {
                'int' => (int) $record[$field],
                'float' => (float) $record[$field],
                'string' => (string) $record[$field],
                'date' => $this->normalizeDate($record[$field]),
                default => $record[$field],
            };
        }

        return $record;
    }

    private function normalizeDate(mixed $value): string
    {
        if ($value instanceof DateTimeImmutable) {
            return $value->format('Y-m-d');
        }

        $stringValue = (string) $value;

        // Try common legacy date formats
        $formats = ['m/d/Y', 'd-m-Y', 'Y/m/d', 'Ymd', 'd.m.Y', 'M d, Y', 'Y-m-d'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $stringValue);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return $stringValue;
    }
}
