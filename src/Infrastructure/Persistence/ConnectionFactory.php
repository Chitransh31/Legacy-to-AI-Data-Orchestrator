<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Exception\LegacyConnectionException;
use PDO;
use PDOException;

final class ConnectionFactory
{
    /** @var array<string, PDO> */
    private array $connections = [];

    /**
     * @param array<string, array{dsn: string, user: string, password: string, options?: array<int, mixed>}> $config
     */
    public function __construct(private array $config)
    {
    }

    public function getConnection(string $name = 'default'): PDO
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if (!isset($this->config[$name])) {
            throw LegacyConnectionException::unreachable($name, "No configuration found for source '{$name}'");
        }

        $cfg = $this->config[$name];

        try {
            $pdo = new PDO(
                $cfg['dsn'],
                $cfg['user'],
                $cfg['password'],
                $cfg['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $this->connections[$name] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            throw LegacyConnectionException::unreachable($name, $e->getMessage());
        }
    }
}
