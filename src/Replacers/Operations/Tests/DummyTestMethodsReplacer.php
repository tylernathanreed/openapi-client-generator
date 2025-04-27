<?php

namespace Reedware\OpenApi\Replacers\Operations\Tests;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\OperationGroup;

class DummyTestMethodsReplacer extends AbstractReplacer
{
    public function replace(string $stub, OperationGroup $schema): string
    {
        $methods = array_map(fn ($operation) => $operation->getTestDefinition(), $schema->operations);

        $content = implode("\n\n", $methods);

        return str_replace('    // DummyTestMethods', $content, $stub);
    }
}
