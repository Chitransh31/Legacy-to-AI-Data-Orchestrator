<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Contract\CacheInterface;
use App\Domain\ValueObject\CacheKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $maxRequests = 60,
        private int $windowSeconds = 60,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $cacheKey = new CacheKey('ratelimit', md5($clientIp));

        $current = (int) ($this->cache->get($cacheKey) ?? '0');

        if ($current >= $this->maxRequests) {
            $response = new Response(429);
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => "Rate limit of {$this->maxRequests} requests per {$this->windowSeconds}s exceeded.",
                ],
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $this->windowSeconds);
        }

        $this->cache->set($cacheKey, (string) ($current + 1), $this->windowSeconds);

        return $handler->handle($request);
    }
}
