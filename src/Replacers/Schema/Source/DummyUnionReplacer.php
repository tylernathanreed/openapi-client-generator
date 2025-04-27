<?php

namespace Reedware\OpenApi\Replacers\Schema\Source;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyUnionReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        if (! $schema->isUnionType()) {
            return str_replace("\n    // DummyUnion", '', $stub);
        }

        $types = '';
        $indent = str_repeat(' ', 12);

        foreach (($schema->unionTypes ?? []) as $type) {
            $types .= "{$indent}{$type}::class,\n";
        }

        $types = rtrim($types, "\n");

        $stub = str_replace("    // DummyUnion\n", <<<DOC

            /** @inheritDoc */
            public function unionTypes(): array
            {
                return [
        {$types}
                ];
            }

        DOC, $stub);

        return str_replace(<<<'DOC'

            public function __construct(

            ) {
            }

        DOC, '', $stub);
    }
}
