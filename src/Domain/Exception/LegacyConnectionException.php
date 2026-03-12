<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class LegacyConnectionException extends OrchestratorException
{
    protected int $httpStatusCode = 503;

    public static function unreachable(string $source, string $reason): self
    {
        $e = new self("Legacy data source '{$source}' is unreachable: {$reason}");
        return $e;
    }

    public static function queryFailed(string $source, string $reason): self
    {
        return new self("Query failed on '{$source}': {$reason}");
    }
}
