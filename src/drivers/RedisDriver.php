<?php

declare(strict_types=1);

namespace Gatovel\Cache\drivers;

use Gatovel\Cache\contracts\CacheInterface;
use Gatovel\Cache\support\CacheValueValidator;
use InvalidArgumentException;
use Predis\ClientInterface;
use RuntimeException;

final class RedisDriver implements CacheInterface
{
    private readonly string $namespace;

    public function __construct(
        private readonly ClientInterface $client,
        string $prefix = 'gatovel:cache'
    ) {
        if (
            $prefix === ''
            || strpbrk($prefix, '|*?[]\\') !== false
        ) {
            throw new InvalidArgumentException(
                'Cache prefix contains invalid characters.'
            );
        }

        $this->namespace = $prefix . '|';
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $value = $this->client->get(
            $this->key($key)
        );

        if ($value === null) {
            return $default;
        }

        $decoded = json_decode(
            $value,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (
            !is_array($decoded)
            || !array_key_exists('value', $decoded)
        ) {
            throw new RuntimeException(
                'Invalid Redis cache data.'
            );
        }

        CacheValueValidator::validate($decoded['value']);

        return $decoded['value'];
    }

    public function put(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): bool {
        $redisKey = $this->key($key);

        if ($ttl !== null && $ttl <= 0) {
            return $this->forget($key);
        }

        CacheValueValidator::validate($value);

        $payload = json_encode(
            ['value' => $value],
            JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
        );

        $result = $ttl === null
            ? $this->client->set($redisKey, $payload)
            : $this->client->setex(
                $redisKey,
                $ttl,
                $payload
            );

        return (string) $result === 'OK';
    }

    public function has(string $key): bool
    {
        return (int) $this->client->exists(
            $this->key($key)
        ) > 0;
    }

    public function forget(string $key): bool
    {
        $this->client->del([
            $this->key($key),
        ]);

        return true;
    }

    public function flush(): bool
    {
        $cursor = '0';

        do {
            [$cursor, $keys] = $this->client->scan(
                $cursor,
                [
                    'MATCH' => $this->namespace . '*',
                    'COUNT' => 100,
                ]
            );

            if ($keys !== []) {
                $this->client->del($keys);
            }
        } while ((string) $cursor !== '0');

        return true;
    }

    private function key(string $key): string
    {
        if ($key === '') {
            throw new InvalidArgumentException(
                'Cache key cannot be empty.'
            );
        }

        return $this->namespace
            . hash('sha256', $key);
    }
}
