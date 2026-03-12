<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Psr\Log\LoggerInterface;

final class ApiHealthLogger
{
    /** @var array<string, array{successes: int, failures: int, latencies: float[]}> */
    private array $metrics = [];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function recordSuccess(string $component, float $latencyMs): void
    {
        $this->ensureComponent($component);
        $this->metrics[$component]['successes']++;
        $this->metrics[$component]['latencies'][] = $latencyMs;
    }

    public function recordFailure(string $component): void
    {
        $this->ensureComponent($component);
        $this->metrics[$component]['failures']++;
    }

    public function flush(): void
    {
        foreach ($this->metrics as $component => $data) {
            $total = $data['successes'] + $data['failures'];
            if ($total === 0) {
                continue;
            }

            $avgLatency = $data['latencies'] !== []
                ? array_sum($data['latencies']) / count($data['latencies'])
                : 0;

            $this->logger->info('Health metrics', [
                'component' => $component,
                'successes' => $data['successes'],
                'failures' => $data['failures'],
                'error_rate' => round($data['failures'] / $total * 100, 2),
                'avg_latency_ms' => round($avgLatency, 2),
            ]);
        }

        $this->metrics = [];
    }

    /** @return array<string, array{status: string, successes: int, failures: int}> */
    public function getComponentStatuses(): array
    {
        $statuses = [];
        foreach ($this->metrics as $component => $data) {
            $total = $data['successes'] + $data['failures'];
            $errorRate = $total > 0 ? $data['failures'] / $total : 0;

            $statuses[$component] = [
                'status' => $errorRate > 0.5 ? 'unhealthy' : ($errorRate > 0.1 ? 'degraded' : 'ok'),
                'successes' => $data['successes'],
                'failures' => $data['failures'],
            ];
        }
        return $statuses;
    }

    private function ensureComponent(string $component): void
    {
        if (!isset($this->metrics[$component])) {
            $this->metrics[$component] = ['successes' => 0, 'failures' => 0, 'latencies' => []];
        }
    }
}
