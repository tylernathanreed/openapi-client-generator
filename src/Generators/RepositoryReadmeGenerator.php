<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Replacers;

class RepositoryReadmeGenerator extends AbstractStaticFileGenerator
{
    protected $replacers = [
        Replacers\Repository\Readme\DummyOperationsListReplacer::class,
        Replacers\Repository\Readme\DummySchemaListReplacer::class,
    ];

    protected function getPath(): string
    {
        return $this->basePath . '/README.md';
    }

    protected function getStub(): string
    {
        return 'README.stub.md';
    }
}
