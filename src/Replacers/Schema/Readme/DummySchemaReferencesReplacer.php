<?php

namespace Reedware\OpenApi\Replacers\Schema\Readme;

use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Markdown\Table;
use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;
use Reedware\OpenApi\Schema\Specification;
use Reedware\OpenApi\Utils;

class DummySchemaReferencesReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        $references = [];
        $schemas = Specification::getComponentSchemas();

        foreach ($schemas as $name => $_schema) {
            if ($name === $schema->name) {
                continue;
            }

            $text = json_encode($_schema) ?: '';

            if (str_contains($text, '"#\/components\/schemas\/' . $schema->name . '"')) {
                $references[] = [$name];
            }
        }

        if (empty($references)) {
            return str_replace('DummySchemaReferences', '*None*', $stub);
        }

        $table = new Table(['Schema']);

        foreach ($references as $reference) {
            [$_schema] = $reference;

            $table->add([
                new Link($_schema, '/docs/schema/' . Utils::slug($_schema) . '.md'),
            ]);
        }

        return str_replace('DummySchemaReferences', $table, $stub);
    }
}
