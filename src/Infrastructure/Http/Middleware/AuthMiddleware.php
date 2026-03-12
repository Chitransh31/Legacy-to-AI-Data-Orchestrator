<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private string $apiKey)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip auth for health endpoint
        if ($request->getUri()->getPath() === '/api/v1/health') {
            return $handler->handle($request);
        }

        $providedKey = $request->getHeaderLine('X-API-Key');

        if ($providedKey === '' || !hash_equals($this->apiKey, $providedKey)) {
            $response = new Response(401);
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Invalid or missing API key.',
                ],
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
