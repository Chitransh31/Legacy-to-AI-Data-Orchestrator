<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\ValueObject\CacheKey;
use App\Domain\ValueObject\DataSourceId;

final class CacheKeyGenerator
{
    public function forTransform(DataSourceId $source, array $query, string $schemaVersion = '1.0'): CacheKey
    {
        $queryHash = md5((string) json_encode($query, JSON_THROW_ON_ERROR));
        return new CacheKey('transform', $source->value(), $queryHash, $schemaVersion);
    }

    public function forLlmResponse(string $payloadJson, string $prompt, string $model): CacheKey
    {
        $payloadHash = md5($payloadJson);
        $promptHash = md5($prompt);
        return new CacheKey('llm', $payloadHash, $promptHash, $model);
    }

    public function sourcePrefix(DataSourceId $source): string
    {
        return 'transform:' . $source->value();
    }
}
