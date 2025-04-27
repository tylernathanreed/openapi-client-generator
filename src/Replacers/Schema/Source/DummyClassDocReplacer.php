<?php

namespace Reedware\OpenApi\Replacers\Schema\Source;

use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;

class DummyClassDocReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        $content = $schema->description->render();

        if (empty($content)) {
            return str_replace("\n// DummyDoc", '', $stub);
        }

        return str_replace("\n// DummyDoc\n", "\n" . $content, $stub);
    }
}
