<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use RuntimeException;

final class ConfigLoader
{
    /** @var array<string, mixed> */
    private array $config = [];
    private bool $loaded = false;

    public function __construct(private string $configPath)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureLoaded();

        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $this->ensureLoaded();
        return $this->config;
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        $files = glob($this->configPath . '/*.php');
        if ($files === false) {
            throw new RuntimeException("Cannot read config directory: {$this->configPath}");
        }

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $this->config[$name] = require $file;
        }

        $this->loaded = true;
    }
}
