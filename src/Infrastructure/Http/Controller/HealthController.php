<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\UseCase\HealthCheckUseCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HealthController
{
    public function __construct(private HealthCheckUseCase $healthCheck)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $health = $this->healthCheck->execute();
        $status = $health['status'] === 'healthy' ? 200 : 503;

        $response->getBody()->write((string) json_encode([
            'success' => true,
            'data' => $health,
            'meta' => [
                'request_id' => $request->getAttribute('request_id'),
                'timestamp' => date('c'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
