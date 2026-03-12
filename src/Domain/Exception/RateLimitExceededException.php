<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class RateLimitExceededException extends OrchestratorException
{
    protected int $httpStatusCode = 429;

    public static function create(int $retryAfter = 60): self
    {
        return new self("Rate limit exceeded. Retry after {$retryAfter}s");
    }
}
