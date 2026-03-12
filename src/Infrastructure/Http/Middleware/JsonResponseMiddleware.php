<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

final class JsonResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = Uuid::uuid4()->toString();
        $request = $request->withAttribute('request_id', $requestId);
        $startTime = microtime(true);

        $response = $handler->handle($request);

        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Request-ID', $requestId)
            ->withHeader('X-Response-Time-Ms', (string) $latencyMs);
    }
}
