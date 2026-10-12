<?php

declare(strict_types=1);

namespace Gatovel\Cache\support;

use InvalidArgumentException;

final class CacheValueValidator
{
    public static function validate(mixed $value): void
    {
        self::validateValue($value);
    }

    private static function validateValue(mixed $value): void
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return;
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new InvalidArgumentException(
                    'Cache does not support INF or NAN.'
                );
            }

            return;
        }

        if (is_string($value)) {
            if (!preg_match('//u', $value)) {
                throw new InvalidArgumentException(
                    'Cache strings must contain valid UTF-8.'
                );
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                self::validateValue($item);
            }

            return;
        }

        throw new InvalidArgumentException(
            'Unsupported cache value type.'
        );
    }
}
