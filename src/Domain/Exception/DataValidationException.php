<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class DataValidationException extends OrchestratorException
{
    protected int $httpStatusCode = 422;

    /** @var array<string, string[]> */
    private array $fieldErrors = [];

    /**
     * @param array<string, string[]> $fieldErrors
     */
    public static function withErrors(array $fieldErrors): self
    {
        $e = new self('Data validation failed: ' . self::summarize($fieldErrors));
        $e->fieldErrors = $fieldErrors;
        return $e;
    }

    /** @return array<string, string[]> */
    public function fieldErrors(): array
    {
        return $this->fieldErrors;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return ['field_errors' => $this->fieldErrors];
    }

    /** @param array<string, string[]> $errors */
    private static function summarize(array $errors): string
    {
        $parts = [];
        foreach ($errors as $field => $messages) {
            $parts[] = "{$field}: " . implode(', ', $messages);
        }
        return implode('; ', $parts);
    }
}
