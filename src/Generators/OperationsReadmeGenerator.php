<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Replacers;
use Reedware\OpenApi\Schema\OperationGroup;

/** @extends AbstractReadmeGenerator<OperationGroup> */
class OperationsReadmeGenerator extends AbstractReadmeGenerator
{
    /** {@inheritDoc} */
    protected $replacers = [
        Replacers\DummyTitleReplacer::class,
        Replacers\Operations\Readme\DummyOperationsListReplacer::class,
        Replacers\Operations\Readme\DummyOperationsReplacer::class,
        Replacers\Readme\DummySourceReplacer::class,
    ];
}
