<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class LlmApiException extends OrchestratorException
{
    protected int $httpStatusCode = 502;

    public static function requestFailed(string $reason): self
    {
        return new self("LLM API request failed: {$reason}");
    }

    public static function timeout(int $timeoutSeconds): self
    {
        return new self("LLM API request timed out after {$timeoutSeconds}s");
    }

    public static function rateLimited(int $retryAfter): self
    {
        $e = new self("LLM API rate limited. Retry after {$retryAfter}s");
        return $e;
    }
}
