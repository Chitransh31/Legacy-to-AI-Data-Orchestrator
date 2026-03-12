<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\DTO\TransformRequest;
use App\Application\UseCase\FetchAndTransformUseCase;
use App\Domain\Contract\CacheInterface;
use App\Domain\ValueObject\DataSourceId;
use App\Infrastructure\Cache\CacheKeyGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DataSourceController
{
    public function __construct(
        private FetchAndTransformUseCase $transformUseCase,
        private CacheInterface $cache,
        private CacheKeyGenerator $cacheKeyGenerator,
        private string $schemasPath,
    ) {
    }

    public function listSources(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $files = glob($this->schemasPath . '/*.php') ?: [];
        $sources = [];

        foreach ($files as $file) {
            $id = basename($file, '.php');
            $schema = require $file;
            $sources[] = [
                'id' => $id,
                'name' => ucwords(str_replace('_', ' ', $id)),
                'table' => $schema['table'] ?? $id,
                'fields' => count($schema['mappings'] ?? []),
            ];
        }

        return $this->json($response, 200, [
            'success' => true,
            'data' => ['sources' => $sources],
        ]);
    }

    public function getSchema(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $sourceId = $args['id'] ?? '';
        $schemaFile = $this->schemasPath . '/' . $sourceId . '.php';

        if (!file_exists($schemaFile)) {
            return $this->json($response, 404, [
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => "Source '{$sourceId}' not found."],
            ]);
        }

        $schema = require $schemaFile;

        return $this->json($response, 200, [
            'success' => true,
            'data' => [
                'source' => $sourceId,
                'mappings' => $schema['mappings'] ?? [],
                'required' => $schema['required'] ?? [],
                'types' => $schema['types'] ?? [],
            ],
        ]);
    }

    public function transform(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $source = $body['source'] ?? '';
        $query = $body['query'] ?? [];

        if ($source === '') {
            return $this->json($response, 422, [
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => "Field 'source' is required."],
            ]);
        }

        $transformRequest = new TransformRequest(new DataSourceId($source), $query);
        $payload = $this->transformUseCase->execute($transformRequest);

        return $this->json($response, 200, [
            'success' => true,
            'data' => [
                'records' => $payload->records(),
                'meta' => [
                    'record_count' => $payload->recordCount(),
                    'estimated_tokens' => $payload->estimatedTokens(),
                    'schema_version' => $payload->schemaVersion(),
                ],
            ],
        ]);
    }

    public function invalidateCache(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $sourceId = $args['sourceId'] ?? '';
        $source = new DataSourceId($sourceId);
        $prefix = $this->cacheKeyGenerator->sourcePrefix($source);

        $this->cache->invalidateByPrefix($prefix);

        return $this->json($response, 200, [
            'success' => true,
            'data' => ['invalidated' => true, 'source' => $sourceId],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function json(ResponseInterface $response, int $status, array $data): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
