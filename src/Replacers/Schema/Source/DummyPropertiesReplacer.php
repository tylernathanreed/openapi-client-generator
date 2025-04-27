<?php

namespace Reedware\OpenApi\Replacers\Schema\Source;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyPropertiesReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        $properties = array_map(fn ($property) => (string) $property, $schema->properties);

        $content = implode("\n\n", $properties);

        return str_replace('{{ DummyProperties }}', $content, $stub);
    }
}
