<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class MonologFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(string $channel, array $config): LoggerInterface
    {
        $logger = new Logger($channel);
        $level = self::parseLevel($config['level'] ?? 'info');
        $logPath = $config['path'] ?? '/tmp';

        // Rotating file handler (daily)
        $fileHandler = new RotatingFileHandler(
            $logPath . '/' . $channel . '.log',
            30,
            $level
        );
        $fileHandler->setFormatter(new JsonFormatter());
        $logger->pushHandler($fileHandler);

        // Stderr for Docker
        if (($config['stderr'] ?? false) || getenv('APP_ENV') === 'production') {
            $stderrHandler = new StreamHandler('php://stderr', $level);
            $stderrHandler->setFormatter(new JsonFormatter());
            $logger->pushHandler($stderrHandler);
        }

        return $logger;
    }

    private static function parseLevel(string $level): Level
    {
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };
    }
}
