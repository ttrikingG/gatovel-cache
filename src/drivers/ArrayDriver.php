<?php

declare(strict_types=1);

namespace Gatovel\Cache\drivers;

use Gatovel\Cache\contracts\CacheInterface;
use Gatovel\Cache\support\CacheValueValidator;
use InvalidArgumentException;

final class ArrayDriver implements CacheInterface
{
    private array $items = [];

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $this->validateKey($key);

        if (!$this->has($key)) {
            return $default;
        }

        return $this->items[$key]['value'];
    }

    public function put(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): bool {
        $this->validateKey($key);

        if ($ttl !== null && $ttl <= 0) {
            unset($this->items[$key]);

            return true;
        }

        CacheValueValidator::validate($value);

        $expiresAt = $ttl === null
            ? null
            : time() + $ttl;

        $this->items[$key] = [
            'value' => $value,
            'expires_at' => $expiresAt,
        ];

        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        if (!array_key_exists($key, $this->items)) {
            return false;
        }

        $expiresAt = $this->items[$key]['expires_at'];

        if (
            $expiresAt !== null
            && time() >= $expiresAt
        ) {
            unset($this->items[$key]);

            return false;
        }

        return true;
    }

    public function forget(string $key): bool
    {
        $this->validateKey($key);

        unset($this->items[$key]);

        return true;
    }

    public function flush(): bool
    {
        $this->items = [];

        return true;
    }

    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException(
                'Cache key cannot be empty.'
            );
        }
    }
}
