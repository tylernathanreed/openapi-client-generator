<?php

namespace Reedware\OpenApi\Generators;

use InvalidArgumentException;
use Reedware\OpenApi\Exceptions\ReservedWordException;

/** @phpstan-template TComposable of object */
abstract class AbstractCompositeGenerator extends AbstractGenerator
{
    /** @var list<class-string<AbstractComposableGenerator<TComposable>> */
    protected array $generators = [];

    public function generate(?string $name = null): void
    {
        $name ??= $this->getDefaultName();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Missing required name for [%s].',
                static::class,
            ));
        }

        if ($this->isReservedName($name)) {
            throw new ReservedWordException($name);
        }

        $composable = $this->resolve($name);

        foreach ($this->generators as $generator) {
            $this->newGenerator($generator)->generate($composable);
        }
    }

    /** @return TComposable */
    abstract protected function resolve(string $name): mixed;

    protected function getDefaultName(): ?string
    {
        return null;
    }

    public function afterAll(): void
    {
        foreach ($this->generators as $generator) {
            if (method_exists($generator, 'afterAll')) {
                $this->newGenerator($generator)->afterAll();
            }
        }
    }
}
