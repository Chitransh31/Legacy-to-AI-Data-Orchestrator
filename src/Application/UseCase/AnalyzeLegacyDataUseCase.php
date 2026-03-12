<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\AnalysisRequest;
use App\Application\DTO\AnalysisResponse;
use App\Application\Pipeline\TransformationPipeline;
use App\Domain\Contract\CacheInterface;
use App\Domain\Contract\LegacyDataRepositoryInterface;
use App\Domain\Contract\LlmGatewayInterface;
use App\Domain\Entity\AnalysisResult;
use App\Domain\Entity\TransformedPayload;
use App\Infrastructure\Cache\CacheKeyGenerator;
use Psr\Log\LoggerInterface;

final class AnalyzeLegacyDataUseCase
{
    public function __construct(
        private LegacyDataRepositoryInterface $repository,
        private TransformationPipeline $pipeline,
        private LlmGatewayInterface $llmGateway,
        private CacheInterface $cache,
        private CacheKeyGenerator $cacheKeyGenerator,
        private LoggerInterface $logger,
        private int $transformCacheTtl = 3600,
        private int $llmCacheTtl = 86400,
    ) {
    }

    public function execute(AnalysisRequest $request): AnalysisResponse
    {
        $startTime = microtime(true);

        // Step 1: Check LLM response cache (full result)
        $transformedPayload = $this->fetchAndTransform($request);
        $llmCacheKey = $this->cacheKeyGenerator->forLlmResponse(
            $transformedPayload->toJson(),
            $request->prompt,
            'default'
        );

        $cachedLlm = $this->cache->get($llmCacheKey);
        if ($cachedLlm !== null) {
            $this->logger->info('LLM cache hit', ['source' => $request->sourceId->value()]);
            $result = $this->deserializeResult($cachedLlm);
            return $this->buildResponse($result->withCached(true), $transformedPayload);
        }

        // Step 2: Call LLM
        $result = $this->llmGateway->analyze($transformedPayload, $request->prompt);

        // Step 3: Cache the LLM response
        $this->cache->set($llmCacheKey, $this->serializeResult($result), $this->llmCacheTtl);

        $this->logger->info('Analysis completed', [
            'source' => $request->sourceId->value(),
            'records' => $transformedPayload->recordCount(),
            'tokens' => $result->tokensUsed(),
            'total_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);

        return $this->buildResponse($result, $transformedPayload);
    }

    private function fetchAndTransform(AnalysisRequest $request): TransformedPayload
    {
        // Check transform cache
        $transformCacheKey = $this->cacheKeyGenerator->forTransform(
            $request->sourceId,
            $request->query
        );

        $cachedTransform = $this->cache->get($transformCacheKey);
        if ($cachedTransform !== null) {
            $this->logger->debug('Transform cache hit', ['source' => $request->sourceId->value()]);
            $data = json_decode($cachedTransform, true, 512, JSON_THROW_ON_ERROR);
            return new TransformedPayload($data['records'], $data['estimated_tokens']);
        }

        // Fetch from legacy DB
        $records = $this->repository->fetchBatch($request->sourceId, $request->query);
        $rawData = array_map(fn($record) => $record->columns(), $records);

        // Run pipeline
        $payload = $this->pipeline->process($rawData);

        // Cache transformed data
        $this->cache->set($transformCacheKey, json_encode([
            'records' => $payload->records(),
            'estimated_tokens' => $payload->estimatedTokens(),
        ], JSON_THROW_ON_ERROR), $this->transformCacheTtl);

        return $payload;
    }

    private function buildResponse(AnalysisResult $result, TransformedPayload $payload): AnalysisResponse
    {
        return new AnalysisResponse(
            content: $result->content(),
            model: $result->model(),
            tokensUsed: $result->tokensUsed(),
            latencyMs: $result->latencyMs(),
            cached: $result->isCached(),
            recordCount: $payload->recordCount(),
        );
    }

    private function serializeResult(AnalysisResult $result): string
    {
        return (string) json_encode($result->toArray(), JSON_THROW_ON_ERROR);
    }

    private function deserializeResult(string $json): AnalysisResult
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return new AnalysisResult(
            $data['content'],
            $data['model'],
            $data['tokens_used'],
            $data['latency_ms'],
            $data['cached'] ?? false
        );
    }
}
