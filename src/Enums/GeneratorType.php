<?php

namespace Reedware\OpenApi\Enums;

enum GeneratorType: string
{
    case Client = 'client';
    case Readme = 'readme';
    case Schema = 'schema';
    case Operations = 'operations';

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(fn ($enum) => $enum->value, static::cases());
    }
}
