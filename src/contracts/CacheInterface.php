<?php

declare(strict_types=1);

namespace Gatovel\Cache\contracts;

interface CacheInterface
{
    public function get(
        string $key,
        mixed $default = null
    ): mixed;

    public function put(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): bool;

    public function has(string $key): bool;

    public function forget(string $key): bool;

    public function flush(): bool;
}
