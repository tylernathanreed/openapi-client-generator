<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Replacers;
use Reedware\OpenApi\Schema\OperationGroup;

/** @extends AbstractTestGenerator<OperationGroup> */
class OperationsTestGenerator extends AbstractTestGenerator
{
    /** {@inheritDoc} */
    protected $replacers = [
        Replacers\DummyClassReplacer::class,
        Replacers\DummyNamespaceReplacer::class,
        Replacers\SortImportsReplacer::class,
        Replacers\Operations\Tests\DummyTestMethodsReplacer::class,
    ];
}
