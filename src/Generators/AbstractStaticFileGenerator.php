<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Replacers\AbstractStaticReplacer;
use Reedware\OpenApi\Replacers\Pipeline;

abstract class AbstractStaticFileGenerator extends AbstractGenerator
{
    use Concerns\InteractsWithStubs;

    /** @var list<class-string<AbstractStaticReplacer> */
    protected $replacers = [];

    public function generate(): string
    {
        $path = $this->getPath();

        $this->filesystem->put($path, $this->build());

        return $path;
    }

    /** @param TComposable $composable */
    abstract protected function getPath(): string;

    protected function build(): string
    {
        return (new Pipeline($this->config))
            ->send($this->stub())
            ->through($this->replacers)
            ->thenReturn();
    }
}
