<?php

namespace Reedware\OpenApi\Replacers\Schema\Source;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyParentReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        if ($schema->isPolymorphic()) {
            return str_replace('DummyParent', 'PolymorphicDto', $stub);
        }

        if ($schema->isUnionType()) {
            return str_replace('DummyParent', 'UnionDto', $stub);
        }

        return str_replace('DummyParent', 'Dto', $stub);
    }
}
