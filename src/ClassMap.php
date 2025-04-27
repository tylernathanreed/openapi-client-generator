<?php

namespace Reedware\OpenApi;

use Reedware\OpenApi\Client\Http\Attributes\MapName;
use Reedware\OpenApi\Client\Http\Attributes\PolymorphicList;
use Reedware\OpenApi\Client\Http\PolymorphicDto;

class ClassMap
{
    /** @var array<class-string,string> */
    protected static array $classMap = [];

    protected static array $mappable = [
        MapName::class,
        PolymorphicList::class,
        PolymorphicDto::class,
    ];

    public static function boot(Configuration $config)
    {
        static::$classMap = array_combine(
            keys: static::$mappable,
            values: array_map(function (string $class) use ($config) {
                return str_replace('Reedware\OpenApi\Client', $config->namespace, $class);
            }, static::$mappable)
        );
    }

    /** @param class-string $class */
    public static function resolve(string $class): string
    {
        return static::$classMap[$class] ?? $class;
    }

    /** @return array<class-string,string> */
    public static function classMap(): array
    {
        return static::$classMap;
    }
}
