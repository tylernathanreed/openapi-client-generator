<?php

namespace Reedware\OpenApi\Replacers\Operations\Source;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\OperationGroup;

class DummyTraitReplacer extends AbstractReplacer
{
    public function replace(string $stub, OperationGroup $schema): string
    {
        return str_replace('DummyTrait', $schema->name, $stub);
    }
}
