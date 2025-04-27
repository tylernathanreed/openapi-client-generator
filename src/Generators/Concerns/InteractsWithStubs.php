<?php

namespace Reedware\OpenApi\Generators\Concerns;

trait InteractsWithStubs
{
    abstract protected function getStub(): string;

    protected function stub(): string
    {
        $relative = 'stubs/' . ltrim($this->getStub(), '/');

        $local = $this->basePath . '/' . $relative;

        $path = $this->filesystem->exists($local = $this->basePath . '/' . $relative)
            ? $local
            : $this->rootPath . '/' . $relative;

        return $this->filesystem->get($path);
    }
}
