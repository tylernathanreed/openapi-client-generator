<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Contracts\ListableGenerator;
use Reedware\OpenApi\Schema\OperationGroup;
use Reedware\OpenApi\Schema\Specification;

/**
 * @extends AbstractCompositeGenerator<OperationGroup>
 */
class OperationsGenerator extends AbstractCompositeGenerator implements ListableGenerator
{
    protected array $generators = [
        OperationsSourceGenerator::class,
        OperationsTestGenerator::class,
        OperationsReadmeGenerator::class,
    ];

    protected function resolve(string $name): OperationGroup
    {
        return Specification::getOperationGroup($name);
    }

    /** @return list<string> */
    public function existing(): array
    {
        return $this->newGenerator(OperationsSourceGenerator::class)->existing();
    }

    public function all(): array
    {
        return array_keys(Specification::getOperationGroups());
    }
}
