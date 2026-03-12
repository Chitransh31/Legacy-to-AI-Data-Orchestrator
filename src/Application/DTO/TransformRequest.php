<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\ValueObject\DataSourceId;

final class TransformRequest
{
    /**
     * @param array<string, mixed> $query
     */
    public function __construct(
        public readonly DataSourceId $sourceId,
        public readonly array $query,
    ) {
    }
}
