<?php

declare(strict_types=1);

namespace Gatovel\Cache\drivers;

use Gatovel\Cache\contracts\CacheInterface;
use Gatovel\Cache\support\CacheValueValidator;
use InvalidArgumentException;
use RuntimeException;

final class FileDriver implements CacheInterface
{
    private readonly string $directory;

    public function __construct(string $directory)
    {
        if ($directory === '') {
            throw new InvalidArgumentException(
                'Cache directory cannot be empty.'
            );
        }

        if (
            !is_dir($directory)
            && !mkdir($directory, 0775, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create cache directory.'
            );
        }

        $resolved = realpath($directory);

        if ($resolved === false || !is_writable($resolved)) {
            throw new RuntimeException(
                'Cache directory is not writable.'
            );
        }

        $this->directory = $resolved;
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        $item = $this->read($key);

        return $item === null
            ? $default
            : $item['value'];
    }

    public function put(
        string $key,
        mixed $value,
        ?int $ttl = null
    ): bool {
        $this->validateKey($key);

        if ($ttl !== null && $ttl <= 0) {
            return $this->forget($key);
        }

        CacheValueValidator::validate($value);

        $record = [
            'key' => $key,
            'value' => $value,
            'expires_at' => $ttl === null
                ? null
                : time() + $ttl,
        ];

        $serialized = serialize($record);

        $path = $this->path($key);

        $temporary = tempnam(
            $this->directory,
            'gatovel-'
        );

        if ($temporary === false) {
            throw new RuntimeException(
                'Unable to create temporary cache file.'
            );
        }

        try {
            if (
                file_put_contents(
                    $temporary,
                    $serialized,
                    LOCK_EX
                ) !== strlen($serialized)
            ) {
                throw new RuntimeException(
                    'Unable to write cache data.'
                );
            }

            if (!rename($temporary, $path)) {
                throw new RuntimeException(
                    'Unable to publish cache file.'
                );
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->read($key) !== null;
    }

    public function forget(string $key): bool
    {
        $this->validateKey($key);

        $path = $this->path($key);

        if (!is_file($path)) {
            return true;
        }

        return unlink($path);
    }

    public function flush(): bool
    {
        $files = glob(
            $this->directory . '/*.cache'
        );

        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file) && !unlink($file)) {
                return false;
            }
        }

        return true;
    }

    private function read(string $key): ?array
    {
        $this->validateKey($key);

        $path = $this->path($key);

        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(
                'Unable to read cache file.'
            );
        }

        $record = @unserialize(
            $contents,
            ['allowed_classes' => false]
        );

        if (
            !is_array($record)
            || !array_key_exists('value', $record)
            || !array_key_exists('expires_at', $record)
            || !isset($record['key'])
            || $record['key'] !== $key
            || (
                $record['expires_at'] !== null
                && !is_int($record['expires_at'])
            )
        ) {
            throw new RuntimeException(
                'Invalid cache file format.'
            );
        }

        $expiresAt = $record['expires_at'];

        if (
            $expiresAt !== null
            && time() >= $expiresAt
        ) {
            $this->forget($key);

            return null;
        }

        CacheValueValidator::validate($record['value']);

        return $record;
    }

    private function path(string $key): string
    {
        return $this->directory
            . '/'
            . hash('sha256', $key)
            . '.cache';
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
