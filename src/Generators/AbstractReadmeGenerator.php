<?php

namespace Reedware\OpenApi\Generators;

use Override;
use Reedware\OpenApi\Schema\AbstractSchema;
use Reedware\OpenApi\Utils;

/**
 * @phpstan-template TSchema of AbstractSchema
 *
 * @extends AbstractComposableFileGenerator<TSchema>
 */
abstract class AbstractReadmeGenerator extends AbstractComposableFileGenerator
{
    #[Override]
    protected function getPath(object $composable): string
    {
        return strtr('{basePath}/docs/{type}/{name}.md', [
            '{basePath}' => $this->basePath,
            '{type}' => Utils::slug($this->type()),
            '{name}' => Utils::slug($this->name($composable)),
        ]);
    }

    #[Override]
    protected function getStub(): string
    {
        return strtr('{type}.stub.md', [
            '{type}' => Utils::slug($this->type()),
        ]);
    }

    protected function type(): string
    {
        return substr(class_basename(static::class), 0, -strlen('ReadmeGenerator'));
    }
}
