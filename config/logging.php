<?php

declare(strict_types=1);

return [
    'level' => $_ENV['LOG_LEVEL'] ?? 'info',
    'path' => dirname(__DIR__) . '/var/log',
    'channels' => ['app', 'api', 'llm', 'legacy', 'cache'],
];
