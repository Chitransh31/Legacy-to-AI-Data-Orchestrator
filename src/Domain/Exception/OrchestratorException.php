<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

abstract class OrchestratorException extends RuntimeException
{
    protected int $httpStatusCode = 500;

    public function httpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return [];
    }
}
