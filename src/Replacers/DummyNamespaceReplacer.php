<?php

namespace Reedware\OpenApi\Replacers;

class DummyNamespaceReplacer extends AbstractReplacer
{
    public function replace(string $stub): string
    {
        return str_replace('DummyNamespace', $this->config->namespace, $stub);
    }
}
