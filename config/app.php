<?php

declare(strict_types=1);

return [
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'api_key' => $_ENV['API_KEY'] ?? '',
    'timezone' => 'UTC',
];
