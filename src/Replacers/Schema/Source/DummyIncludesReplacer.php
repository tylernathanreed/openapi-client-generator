<?php

namespace Reedware\OpenApi\Replacers\Schema\Source;

use DateTimeImmutable;
use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyIncludesReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        $includes = [];

        if ($schema->hasDateTime()) {
            $includes[] = DateTimeImmutable::class;
        }

        foreach ($schema->getPropertyAttributes() as $attribute) {
            $includes[] = $attribute;
        }

        $content = implode("\n", array_map(fn ($v) => "use {$v};", $includes));

        if (! empty($content)) {
            $content .= "\n";
        }

        return str_replace("// DummyIncludes\n", $content, $stub);
    }
}
