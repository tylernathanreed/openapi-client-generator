<?php

namespace Reedware\OpenApi\Replacers\Operations\Readme;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\OperationGroup;

class DummyOperationsReplacer extends AbstractReplacer
{
    public function replace(string $stub, OperationGroup $schema): string
    {
        $contents = '';

        foreach ($schema->operations as $operation) {
            $contents .= $operation->toMarkdown() . "\n";
        }

        $contents = rtrim($contents);

        return str_replace('DummyOperations', $contents, $stub);
    }
}
