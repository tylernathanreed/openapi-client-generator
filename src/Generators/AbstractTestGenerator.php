<?php

namespace Reedware\OpenApi\Generators;

use Override;
use Reedware\OpenApi\Schema\AbstractSchema;

/**
 * @phpstan-template TSchema of AbstractSchema
 *
 * @extends AbstractComposableFileGenerator<TSchema>
 */
abstract class AbstractTestGenerator extends AbstractComposableFileGenerator
{
    #[Override]
    protected function getPath(object $composable): string
    {
        return strtr('{basePath}/tests/Unit/{type}/{name}Test.php', [
            '{basePath}' => $this->basePath,
            '{type}' => $this->type(),
            '{name}' => ucfirst($this->name($composable)),
        ]);
    }

    #[Override]
    protected function getStub(): string
    {
        return strtr('{type}Test.stub.php', [
            '{type}' => $this->type(),
        ]);
    }

    protected function type(): string
    {
        return substr(class_basename(static::class), 0, -strlen('TestGenerator'));
    }
}
