<?php

declare(strict_types=1);

return [
    'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
    'redis' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
    ],
    'file' => [
        'path' => dirname(__DIR__) . '/var/cache',
    ],
    'ttl' => [
        'transform' => 3600,    // 1 hour for transformed data
        'llm' => 86400,         // 24 hours for LLM responses
    ],
];
