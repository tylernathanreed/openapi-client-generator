<?php

namespace Reedware\OpenApi\Replacers\Repository\Readme;

use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Specification;
use Reedware\OpenApi\Utils;

class DummySchemaListReplacer extends AbstractReplacer
{
    public function replace(string $stub): string
    {
        $schemas = Specification::getComponentSchemas();

        $contents = '';

        $header = null;

        foreach ($schemas as $name => $schema) {
            $letter = $name[0];

            if ($letter !== $header) {
                if (! is_null($header)) {
                    $contents .= "\n";
                }

                $header = $letter;
                $contents .= "#### {$header}\n";
            }

            $contents .= '- ' . new Link($name, '/docs/schema/' . Utils::slug($name) . '.md') . "\n";
        }

        return str_replace('DummySchemaList', $contents, $stub);
    }
}
