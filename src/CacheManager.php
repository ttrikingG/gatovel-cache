<?php

declare(strict_types=1);

namespace Gatovel\Cache;

use Gatovel\Cache\contracts\CacheInterface;
use InvalidArgumentException;

final class CacheManager
{
    /**
     * @var array<string, CacheInterface>
     */
    private array $drivers = [];

    private ?string $defaultDriver = null;

    public function extend(
        string $name,
        CacheInterface $driver
    ): self {
        if ($name === '') {
            throw new InvalidArgumentException(
                'Cache driver name cannot be empty.'
            );
        }

        $this->drivers[$name] = $driver;

        if ($this->defaultDriver === null) {
            $this->defaultDriver = $name;
        }

        return $this;
    }

    public function setDefaultDriver(string $name): self
    {
        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException(
                "Cache driver '{$name}' is not registered."
            );
        }

        $this->defaultDriver = $name;

        return $this;
    }

    public function driver(?string $name = null): CacheInterface
    {
        $name ??= $this->defaultDriver;

        if ($name === null || !isset($this->drivers[$name])) {
            throw new InvalidArgumentException(
                'Requested cache driver is not registered.'
            );
        }

        return $this->drivers[$name];
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->driver()->get($key, $default);
    }

    public function put(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): bool {
        return $this->driver()->put($key, $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->driver()->has($key);
    }

    public function forget(string $key): bool
    {
        return $this->driver()->forget($key);
    }

    public function flush(): bool
    {
        return $this->driver()->flush();
    }

    public function remember(
        string $key,
        ?int $ttl,
        callable $callback
    ): mixed {
        $driver = $this->driver();

        if ($driver->has($key)) {
            return $driver->get($key);
        }

        $value = $callback();

        $driver->put($key, $value, $ttl);

        return $value;
    }
}
