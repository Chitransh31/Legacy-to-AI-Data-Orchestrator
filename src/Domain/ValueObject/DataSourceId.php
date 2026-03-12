<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class DataSourceId
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidArgumentException('DataSourceId cannot be empty.');
        }
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $trimmed)) {
            throw new InvalidArgumentException(
                "DataSourceId must be lowercase alphanumeric with underscores: '{$trimmed}'"
            );
        }
        $this->value = $trimmed;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
