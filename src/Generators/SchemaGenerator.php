<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Contracts\ListableGenerator;
use Reedware\OpenApi\Schema\Schema;
use Reedware\OpenApi\Schema\Specification;

/** @extends AbstractCompositeGenerator<Schema> */
class SchemaGenerator extends AbstractCompositeGenerator implements ListableGenerator
{
    protected array $generators = [
        SchemaSourceGenerator::class,
        SchemaReadmeGenerator::class,
    ];

    protected function resolve(string $name): Schema
    {
        return Specification::getComponentSchema($name);
    }

    /** @return list<string> */
    public function existing(): array
    {
        return $this->newGenerator(SchemaSourceGenerator::class)->existing();
    }

    /** @return list<string> */
    public function all(): array
    {
        return array_keys(Specification::getComponentSchemas());
    }
}
