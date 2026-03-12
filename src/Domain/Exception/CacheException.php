<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class CacheException extends OrchestratorException
{
    protected int $httpStatusCode = 500;

    public static function connectionFailed(string $reason): self
    {
        return new self("Cache connection failed: {$reason}");
    }
}
