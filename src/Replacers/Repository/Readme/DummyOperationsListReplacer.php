<?php

namespace Reedware\OpenApi\Replacers\Repository\Readme;

use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Specification;
use Reedware\OpenApi\Utils;

class DummyOperationsListReplacer extends AbstractReplacer
{
    public function replace(string $stub): string
    {
        $groups = Specification::getOperationGroups();

        $contents = '';

        foreach ($groups as $name => $group) {
            $header = Utils::title($name);

            if (! empty($contents)) {
                $contents .= "\n";
            }

            $contents .= "#### {$header}\n";

            foreach ($group as $id => $operation) {
                $contents .= '- ' . new Link(
                    Utils::title($operation['operation']['summary'] ?? $operation['id']),
                    '/docs/operations/' . Utils::slug($name) . '.md#' . $id
                ) . "\n";
            }
        }

        return str_replace('DummyOperationsList', $contents, $stub);
    }
}
