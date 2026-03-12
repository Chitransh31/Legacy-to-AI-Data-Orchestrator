<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\DTO\AnalysisRequest;
use App\Application\UseCase\AnalyzeLegacyDataUseCase;
use App\Domain\ValueObject\DataSourceId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AnalysisController
{
    public function __construct(
        private AnalyzeLegacyDataUseCase $analyzeUseCase,
    ) {
    }

    public function analyze(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) $request->getParsedBody();

        $source = $body['source'] ?? '';
        $query = $body['query'] ?? [];
        $prompt = $body['prompt'] ?? '';

        if ($source === '' || $prompt === '') {
            return $this->json($response, 422, [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => "Fields 'source' and 'prompt' are required.",
                ],
            ]);
        }

        $analysisRequest = new AnalysisRequest(
            new DataSourceId($source),
            $query,
            $prompt
        );

        $result = $this->analyzeUseCase->execute($analysisRequest);

        return $this->json($response, 200, [
            'success' => true,
            'data' => $result->toArray(),
            'meta' => [
                'request_id' => $request->getAttribute('request_id'),
                'timestamp' => date('c'),
                'cached' => $result->cached,
                'latency_ms' => $result->latencyMs,
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function json(ResponseInterface $response, int $status, array $data): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
