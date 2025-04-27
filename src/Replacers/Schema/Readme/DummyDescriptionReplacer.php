<?php

namespace Reedware\OpenApi\Replacers\Schema\Readme;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyDescriptionReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        $content = $schema->description->toMarkdown();

        if (empty($content)) {
            return str_replace("\nDummyDescription", '', $stub);
        }

        return str_replace("\nDummyDescription", "\n" . $content, $stub);
    }
}
