<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Replacers\AbstractComposableReplacer;
use Reedware\OpenApi\Replacers\Pipeline;
use Reedware\OpenApi\Schema\OperationGroup;
use Reedware\OpenApi\Schema\Schema;

/** @phpstan-template TComposable of Schema|OperationGroup */
abstract class AbstractComposableFileGenerator extends AbstractGenerator
{
    use Concerns\InteractsWithStubs;

    /** @var list<class-string<AbstractComposableReplacer<TComposable>> */
    protected $replacers = [];

    /** @param TComposable $composable */
    public function generate(object $composable): string
    {
        $path = $this->getPath($composable);

        $this->filesystem->put($path, $this->build($composable));

        return $path;
    }

    /** @param TComposable $composable */
    protected function name(object $composable): string
    {
        return $composable->name;
    }

    /** @param TComposable $composable */
    abstract protected function getPath(object $composable): string;

    /** @param TComposable $composable */
    protected function build(object $composable): string
    {
        return (new Pipeline($this->config))
            ->send($this->stub())
            ->through($this->replacers)
            ->with($composable)
            ->thenReturn();
    }
}
