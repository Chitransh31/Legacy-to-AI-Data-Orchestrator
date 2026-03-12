<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\TransformRequest;
use App\Application\Pipeline\TransformationPipeline;
use App\Domain\Contract\CacheInterface;
use App\Domain\Contract\LegacyDataRepositoryInterface;
use App\Domain\Entity\TransformedPayload;
use App\Infrastructure\Cache\CacheKeyGenerator;
use Psr\Log\LoggerInterface;

final class FetchAndTransformUseCase
{
    public function __construct(
        private LegacyDataRepositoryInterface $repository,
        private TransformationPipeline $pipeline,
        private CacheInterface $cache,
        private CacheKeyGenerator $cacheKeyGenerator,
        private LoggerInterface $logger,
        private int $cacheTtl = 3600,
    ) {
    }

    public function execute(TransformRequest $request): TransformedPayload
    {
        $cacheKey = $this->cacheKeyGenerator->forTransform($request->sourceId, $request->query);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $data = json_decode($cached, true, 512, JSON_THROW_ON_ERROR);
            return new TransformedPayload($data['records'], $data['estimated_tokens']);
        }

        $records = $this->repository->fetchBatch($request->sourceId, $request->query);
        $rawData = array_map(fn($record) => $record->columns(), $records);

        $payload = $this->pipeline->process($rawData);

        $this->cache->set($cacheKey, json_encode([
            'records' => $payload->records(),
            'estimated_tokens' => $payload->estimatedTokens(),
        ], JSON_THROW_ON_ERROR), $this->cacheTtl);

        $this->logger->info('Transform completed', [
            'source' => $request->sourceId->value(),
            'records' => $payload->recordCount(),
            'tokens' => $payload->estimatedTokens(),
        ]);

        return $payload;
    }
}
