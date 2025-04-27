<?php

namespace Reedware\OpenApi\Generators;

use Generator;
use RuntimeException;

class ClientGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $sourcePath = $this->sourcePath();
        $basePath = $this->basePath();

        foreach ($this->files() as $path) {
            $destinationPath = str_replace($sourcePath, $basePath, $path);

            $this->filesystem->put($destinationPath, $this->build($path));
        }
    }

    protected function build(string $path): string
    {
        $stub = $this->filesystem->get($path);

        $stub = str_replace('Reedware\OpenApi\Client', $this->config->namespace, $stub);

        if (! preg_match('/(?P<imports>(?:^use [^;{]+;$\n?)+)/m', $stub, $match)) {
            return $stub;
        }

        $imports = explode("\n", trim($match['imports']));

        sort($imports);

        return str_replace(trim($match['imports']), implode("\n", $imports), $stub);
    }

    /** @return Generator<string,string> */
    protected function files(): Generator
    {
        foreach ($this->glob($this->sourcePath() . '/*.php') as $file) {
            yield $file;
        }
    }

    /** @return Generator<int,string> */
    protected function glob(string $pattern, int $flags = 0): Generator
    {
        $files = glob($pattern, $flags);

        foreach ($files as $file) {
            yield $file;
        }

        $directories = glob(dirname($pattern) . '/*', GLOB_ONLYDIR|GLOB_NOSORT);

        foreach ($directories as $directory) {
            foreach ($this->glob($directory . '/' . basename($pattern), $flags) as $file) {
                yield $file;
            }
        }
    }

    protected function sourcePath(): string
    {
        $path = realpath(__DIR__ . '/../../vendor/reedware/openapi-client/src');

        if ($path === false) {
            throw new RuntimeException('Unable to find source path for client files.');
        }

        return $path;
    }

    protected function basePath(): string
    {
        $path = realpath($this->basePath . '/' . $this->config->src);

        if ($path === false) {
            throw new RuntimeException('Unable to find base path for client files.');
        }

        return $path;
    }
}
