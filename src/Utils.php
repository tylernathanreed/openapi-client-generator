<?php

namespace Reedware\OpenApi;

class Utils
{
    public static function slug(string $value): string
    {
        $value = static::title($value);

        $value = static::kebab(strtolower($value));

        $value = str_replace(['/', '(', ')'], '-', $value);

        $value = str_replace(['{', '}'], '', $value);

        return $value;
    }

    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        return strtolower(
            preg_replace(
                pattern: '/(.)(?=[A-Z])/u',
                replacement: '$1' . $delimiter,
                subject: preg_replace('/\s+/u', '', ucwords($value)) ?: ''
            ) ?: ''
        );
    }

    public static function title(string $value): string
    {
        $title = mb_convert_case(
            static::snake($value, ' '),
            MB_CASE_TITLE,
            'UTF-8'
        );

        do {
            $newTitle = preg_replace('/([A-Z]) ([A-Z])(?: |$)/', '$1$2 ', $title) ?: '';
            $updated = $newTitle !== $title;
            $title = $newTitle;
        } while ($updated);

        return $title;
    }
}
