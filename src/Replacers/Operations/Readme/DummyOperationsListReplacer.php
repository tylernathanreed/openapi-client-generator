<?php

namespace Reedware\OpenApi\Replacers\Operations\Readme;

use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\OperationGroup;

class DummyOperationsListReplacer extends AbstractReplacer
{
    public function replace(string $stub, OperationGroup $schema): string
    {
        $list = '';

        foreach ($schema->operations as $operation) {
            $list .= '- ' . new Link($operation->summary, '#' . $operation->id) . "\n";
        }

        $list = rtrim($list);

        return str_replace('DummyOperationsList', $list, $stub);
    }
}
