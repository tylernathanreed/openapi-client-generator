<?php

namespace Reedware\OpenApi\Replacers\Schema\Source;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyPolymorphismReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        if (! $schema->isPolymorphic()) {
            return str_replace("\n\n    // DummyPolymorphism", '', $stub);
        }

        $map = '';
        $indent = str_repeat(' ', 12);

        foreach (($schema->discriminatorMap ?? []) as $key => $class) {
            $map .= "{$indent}'{$key}' => {$class}::class,\n";
        }

        $map = rtrim($map, "\n");

        $stub = str_replace("    // DummyPolymorphism\n", <<<DOC
            public static function discriminator(): string
            {
                return '{$schema->discriminatorKey}';
            }

            /** @inheritDoc */
            public static function discriminatorMap(): array
            {
                return [
        {$map}
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
