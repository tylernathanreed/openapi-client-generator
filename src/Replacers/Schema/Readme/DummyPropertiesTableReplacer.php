<?php

namespace Reedware\OpenApi\Replacers\Schema\Readme;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyPropertiesTableReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        return str_replace('DummyPropertiesTable', $schema->getPropertiesMarkdown(), $stub);
    }
}
