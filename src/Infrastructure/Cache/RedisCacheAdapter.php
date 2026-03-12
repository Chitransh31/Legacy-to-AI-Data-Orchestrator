<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\Contract\CacheInterface;
use App\Domain\ValueObject\CacheKey;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Throwable;

final class RedisCacheAdapter implements CacheInterface
{
    public function __construct(
        private RedisClient $redis,
        private LoggerInterface $logger,
    ) {
    }

    public function get(CacheKey $key): ?string
    {
        try {
            $value = $this->redis->get($key->value());
            if ($value === null) {
                $this->logger->debug('Cache miss', ['key' => $key->value()]);
                return null;
            }

            $this->logger->debug('Cache hit', ['key' => $key->value()]);
            return $value;
        } catch (Throwable $e) {
            $this->logger->warning('Cache get failed', [
                'key' => $key->value(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function set(CacheKey $key, string $value, int $ttl = 3600): void
    {
        try {
            $this->redis->setex($key->value(), $ttl, $value);
        } catch (Throwable $e) {
            $this->logger->warning('Cache set failed', [
                'key' => $key->value(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function has(CacheKey $key): bool
    {
        try {
            return (bool) $this->redis->exists($key->value());
        } catch (Throwable $e) {
            $this->logger->warning('Cache exists check failed', [
                'key' => $key->value(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function invalidate(CacheKey $key): void
    {
        try {
            $this->redis->del([$key->value()]);
        } catch (Throwable $e) {
            $this->logger->warning('Cache invalidation failed', [
                'key' => $key->value(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function invalidateByPrefix(string $prefix): void
    {
        try {
            $keys = $this->redis->keys($prefix . ':*');
            if ($keys !== []) {
                $this->redis->del($keys);
                $this->logger->info('Cache invalidated by prefix', [
                    'prefix' => $prefix,
                    'count' => count($keys),
                ]);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Cache prefix invalidation failed', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
