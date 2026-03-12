<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Contract\CacheInterface;
use App\Domain\ValueObject\CacheKey;
use App\Infrastructure\Persistence\ConnectionFactory;
use GuzzleHttp\Client;
use Throwable;

final class HealthCheckUseCase
{
    private float $startedAt;

    public function __construct(
        private ConnectionFactory $connectionFactory,
        private CacheInterface $cache,
        private Client $httpClient,
    ) {
        $this->startedAt = microtime(true);
    }

    /** @return array<string, mixed> */
    public function execute(): array
    {
        $components = [
            'legacy_db' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'llm_api' => $this->checkLlmApi(),
        ];

        $allOk = array_reduce(
            $components,
            fn(bool $carry, array $c) => $carry && $c['status'] === 'ok',
            true
        );

        return [
            'status' => $allOk ? 'healthy' : 'degraded',
            'components' => $components,
            'uptime_seconds' => round(microtime(true) - $this->startedAt, 0),
        ];
    }

    /** @return array{status: string, latency_ms?: float, error?: string} */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            $pdo = $this->connectionFactory->getConnection();
            $pdo->query('SELECT 1');
            return [
                'status' => 'ok',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /** @return array{status: string, latency_ms?: float, error?: string} */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = new CacheKey('health', 'check');
            $this->cache->set($key, 'ok', 10);
            $value = $this->cache->get($key);
            return [
                'status' => $value === 'ok' ? 'ok' : 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    /** @return array{status: string, latency_ms?: float, error?: string} */
    private function checkLlmApi(): array
    {
        try {
            $start = microtime(true);
            $response = $this->httpClient->get('/models', ['timeout' => 5]);
            return [
                'status' => $response->getStatusCode() === 200 ? 'ok' : 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
}
