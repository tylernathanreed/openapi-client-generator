<?php

namespace Reedware\OpenApi\Replacers\Schema\Readme;

use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Markdown\Table;
use Reedware\OpenApi\Replacers\AbstractReplacer;
use Reedware\OpenApi\Schema\Schema;
use Reedware\OpenApi\Schema\Specification;
use Reedware\OpenApi\Utils;

class DummyOperationReferencesReplacer extends AbstractReplacer
{
    public function replace(string $stub, Schema $schema): string
    {
        $references = [];
        $groups = Specification::getOperationGroups();

        foreach ($groups as $group => $operations) {
            foreach ($operations as $operation => $definition) {
                $text = json_encode($definition['operation']) ?: '';

                if (str_contains($text, '"#\/components\/schemas\/' . $schema->name . '"')) {
                    $references[] = [$group, $operation];
                }
            }
        }

        if (empty($references)) {
            return str_replace('DummyOperationReferences', '*None*', $stub);
        }

        $table = new Table(['Group', 'Operation']);

        foreach ($references as $reference) {
            [$group, $operation] = $reference;

            $base = '/docs/operations/' . Utils::slug($group) . '.md';

            $table->add([
                new Link($group, $base),
                new Link($operation, $base . '#' . Utils::slug($operation)),
            ]);
        }

        return str_replace('DummyOperationReferences', $table, $stub);
    }
}
