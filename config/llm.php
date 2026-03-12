<?php

declare(strict_types=1);

return [
    'api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
    'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-4o',
    'max_tokens' => (int) ($_ENV['OPENAI_MAX_TOKENS'] ?? 4096),
    'timeout' => (int) ($_ENV['OPENAI_TIMEOUT'] ?? 30),
    'base_url' => $_ENV['OPENAI_BASE_URL'] ?? 'https://api.openai.com/v1',
    'retry_attempts' => 3,
    'retry_delay_ms' => 1000,
];
