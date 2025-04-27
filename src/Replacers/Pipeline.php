<?php

namespace Reedware\OpenApi\Replacers;

use Reedware\OpenApi\Configuration;

class Pipeline
{
    protected string $stub;

    /** @var list<class-string<AbstractReplacer>> */
    protected array $replacers = [];

    /** @var list<mixed> */
    protected array $parameters = [];

    public function __construct(
        protected Configuration $config,
    ) {
    }

    public function send(string $stub): static
    {
        $this->stub = $stub;

        return $this;
    }

    /** @param list<class-string<AbstractReplacer>> $replacers */
    public function through(array $replacers): static
    {
        $this->replacers = $replacers;

        return $this;
    }

    public function with(...$parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function thenReturn(): string
    {
        $stub = $this->stub;

        foreach ($this->replacers as $class) {
            $replacer = new $class($this->config);

            $stub = $replacer->replace($stub, ...$this->parameters);
        }

        return $stub;
    }
}
