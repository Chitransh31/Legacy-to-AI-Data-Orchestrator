<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\Contract\CacheInterface;
use App\Domain\ValueObject\CacheKey;
use Psr\Log\LoggerInterface;

final class FileCacheAdapter implements CacheInterface
{
    public function __construct(
        private string $cachePath,
        private LoggerInterface $logger,
    ) {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function get(CacheKey $key): ?string
    {
        $file = $this->filePath($key);

        if (!file_exists($file)) {
            $this->logger->debug('File cache miss', ['key' => $key->value()]);
            return null;
        }

        $data = unserialize((string) file_get_contents($file));

        if (!is_array($data) || ($data['expires_at'] ?? 0) < time()) {
            @unlink($file);
            $this->logger->debug('File cache expired', ['key' => $key->value()]);
            return null;
        }

        $this->logger->debug('File cache hit', ['key' => $key->value()]);
        return $data['value'];
    }

    public function set(CacheKey $key, string $value, int $ttl = 3600): void
    {
        $file = $this->filePath($key);
        $data = serialize([
            'value' => $value,
            'expires_at' => time() + $ttl,
        ]);

        file_put_contents($file, $data, LOCK_EX);
    }

    public function has(CacheKey $key): bool
    {
        return $this->get($key) !== null;
    }

    public function invalidate(CacheKey $key): void
    {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function invalidateByPrefix(string $prefix): void
    {
        $pattern = $this->cachePath . '/' . md5($prefix) . '*';
        $files = glob($pattern);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        $this->logger->info('File cache invalidated by prefix', [
            'prefix' => $prefix,
            'count' => count($files),
        ]);
    }

    private function filePath(CacheKey $key): string
    {
        return $this->cachePath . '/' . md5($key->value()) . '.cache';
    }
}
