<?php

namespace Reedware\OpenApi\Replacers\Operations\Source;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\OperationGroup;

class DummyMethodsReplacer extends AbstractReplacer
{
    public function replace(string $stub, OperationGroup $schema): string
    {
        $methods = array_map(fn ($operation) => (string) $operation, $schema->operations);

        $content = implode("\n\n", $methods);

        return str_replace('    // DummyMethods', $content, $stub);
    }
}
