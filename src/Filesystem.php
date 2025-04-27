<?php

namespace Reedware\OpenApi;

use Reedware\OpenApi\Exceptions\FileNotFoundException;
use Reedware\OpenApi\Exceptions\FileWriteException;

class Filesystem
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function get(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new FileNotFoundException("File does not exist at path [{$path}].");
        }

        return $contents;
    }

    public function put(string $path, string $contents): int
    {
        $this->ensureDirectoryExists($path);

        $response = file_put_contents($path, $contents);

        if ($response === false) {
            throw new FileWriteException("Failed to write to [{$path}].");
        }

        return $response;
    }

    public function isDirectory(string $directory): bool
    {
        return is_dir(dirname($directory));
    }

    public function ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true): void
    {
        if (! $this->isDirectory($path)) {
            $this->makeDirectory($path, $mode, $recursive);
        }
    }

    public function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        return mkdir(dirname($path), $mode, $recursive);
    }
}
