<?php

namespace Reedware\OpenApi\Schema\Concerns;

trait ResolvesSafeNames
{
    protected static function resolveSafeName(string $name): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_\- ]/', '', $name);

        assert(is_string($sanitized));

        $words = explode(' ', str_replace(['-', '_'], ' ', $sanitized));

        $studlyWords = array_map('ucfirst', $words);

        $studlyName = implode($studlyWords);

        $camelName = lcfirst($studlyName);

        return is_numeric($camelName[0])
            ? '_' . $camelName
            : $camelName;
    }
}
