<?php

namespace Reedware\OpenApi\Schema\Concerns;

trait ParsesReferences
{
    /** @return array{0:?string,1:?bool} */
    protected static function ref(?string $ref): array
    {
        if (is_null($ref)) {
            return [null, null];
        }

        if (str_starts_with($ref, '#/components/schemas/')) {
            return [ucfirst(basename($ref)), true];
        }

        $type = match ($ref) {
            'number' => 'float',
            'integer' => 'int',
            'boolean' => 'bool',
            default => $ref,
        };

        return [$type, false];
    }
}
