<?php

namespace Reedware\OpenApi\Specification;

class Fix
{
    /** @param array<int|string,mixed> $array */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * @param array<int|string,mixed> $array
     * @param array<int|string,mixed> $value
     * @return array<int|string,mixed>
     */
    public static function merge(array &$array, string $key, array $value): array
    {
        return static::set($array, $key, [
            // @phpstan-ignore arrayUnpacking.nonIterable (assume array)
            ...static::get($array, $key, []),
            ...$value,
        ]);
    }

    /**
     * @param array<int|string,mixed> $array
     * @return array<int|string,mixed>
     */
    public static function set(array &$array, string $key, mixed $value): array
    {
        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}
