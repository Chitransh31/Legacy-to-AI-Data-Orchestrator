<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalApi;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

final class HttpClientFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): Client
    {
        $stack = HandlerStack::create();

        // Add request ID header
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withHeader('X-Request-ID', bin2hex(random_bytes(16)));
        }));

        return new Client([
            'handler' => $stack,
            'base_uri' => $config['base_url'] ?? 'https://api.openai.com/v1',
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'Authorization' => 'Bearer ' . ($config['api_key'] ?? ''),
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
