<?php

namespace Reedware\OpenApi\Replacers\Readme;

use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\OperationGroup;
use Reedware\OpenApi\Schema\Schema;

class DummySourceReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema|OperationGroup $schema): string
    {
        $namespace = match ($schema::class) {
            Schema::class => 'Schema',
            OperationGroup::class => 'Operations',
        };

        $link = new Link(
            "`{$this->config->namespace}\\{$namespace}\\{$schema->name}`",
            "/src/{$namespace}/{$schema->name}.php",
        );

        return str_replace('DummySource', $link, $stub);
    }
}
