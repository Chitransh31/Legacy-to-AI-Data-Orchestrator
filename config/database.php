<?php

declare(strict_types=1);

return [
    'default' => [
        'dsn' => $_ENV['LEGACY_DB_DSN'] ?? 'mysql:host=127.0.0.1;dbname=legacy_erp',
        'user' => $_ENV['LEGACY_DB_USER'] ?? 'root',
        'password' => $_ENV['LEGACY_DB_PASS'] ?? '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
