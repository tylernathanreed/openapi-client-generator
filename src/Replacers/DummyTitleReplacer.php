<?php

namespace Reedware\OpenApi\Replacers;

use Reedware\OpenApi\Schema\OperationGroup;
use Reedware\OpenApi\Schema\Schema;
use Reedware\OpenApi\Utils;

class DummyTitleReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema|OperationGroup $schema): string
    {
        return str_replace('DummyTitle', Utils::title($schema->name), $stub);
    }
}
