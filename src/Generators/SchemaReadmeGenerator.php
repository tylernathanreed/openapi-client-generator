<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Replacers;
use Reedware\OpenApi\Schema\Schema;

/** @extends AbstractReadmeGenerator<Schema> */
class SchemaReadmeGenerator extends AbstractReadmeGenerator
{
    /** {@inheritDoc} */
    protected $replacers = [
        Replacers\DummyTitleReplacer::class,
        Replacers\Readme\DummySourceReplacer::class,
        Replacers\Schema\Readme\DummyDescriptionReplacer::class,
        Replacers\Schema\Readme\DummyOperationReferencesReplacer::class,
        Replacers\Schema\Readme\DummyPropertiesTableReplacer::class,
        Replacers\Schema\Readme\DummySchemaReferencesReplacer::class,
    ];
}
