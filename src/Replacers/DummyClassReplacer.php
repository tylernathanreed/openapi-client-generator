<?php

namespace Reedware\OpenApi\Replacers;

use Reedware\OpenApi\Schema\OperationGroup;
use Reedware\OpenApi\Schema\Schema;

class DummyClassReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema|OperationGroup $schema): string
    {
        return str_replace('DummyClass', $schema->name, $stub);
    }
}
