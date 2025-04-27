<?php

namespace Reedware\OpenApi\Generators;

use Generator;
use InvalidArgumentException;
use Reedware\OpenApi\Contracts\ListableGenerator;
use Reedware\OpenApi\Enums\GeneratorType;
use Throwable;

class OpenApiGenerator extends AbstractGenerator
{
    /** @return Generator<int,Response> */
    public function generate(?GeneratorType $type, ?string $name = null): Generator
    {
        return match ($type) {
            GeneratorType::Client => $this->generateClient(),
            GeneratorType::Readme => $this->generateReadme(),
            GeneratorType::Schema => $this->generateListable(SchemaGenerator::class, $type->name, $name),
            GeneratorType::Operations => $this->generateListable(OperationsGenerator::class, $type->name, $name),
            default => $this->generateAll(),
        };
    }

    /** @return Generator<int,Response> */
    protected function generateClient(): Generator
    {
        $this->newGenerator(ClientGenerator::class)->generate();

        yield Response::success('Client created successfully.');
    }

    /** @return Generator<int,Response> */
    protected function generateReadme(): Generator
    {
        $this->newGenerator(RepositoryReadmeGenerator::class)->generate();

        yield Response::success('Repository README created successfully.');
    }

    /**
     * @param class-string<ListableGenerator> $class
     * @return Generator<int,Response>
     */
    protected function generateListable(string $class, string $type, ?string $name): Generator
    {
        $generator = $this->newGenerator($class);
        assert($generator instanceof ListableGenerator);
        
        $names = $generator->all();

        if (! is_null($name)) {
            if (! in_array($name, $names)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid name "%s". Must be one of: "%s"',
                    $name,
                    implode(", ", $names)
                ));
            }

            $names = [$name];
        }

        $generated = [];

        $missing = is_null($name)
            ? array_fill_keys($generator->existing(), true)
            : [];

        foreach ($names as $name) {
            if (isset($generated[ucfirst($name)])) {
                continue;
            }

            try {
                $generator->generate($name);
            } catch (Throwable $e) {
                yield Response::error("Failed to generate {$type} [{$name}].");

                throw $e;
            }

            yield Response::success("{$type} [{$name}] created successfully.");

            $generated[$name] = true;
            unset($missing[$name]);
        }

        $missing = array_keys($missing);

        foreach ($missing as $name) {
            yield Response::warn("{$type} [{$name}] is missing!");
        }

        $generator->afterAll();
    }

    /** @return Generator<int,Response> */
    protected function generateAll(): Generator
    {
        foreach (GeneratorType::cases() as $type) {
            foreach ($this->generate($type) as $response) {
                yield $response;
            }
        }
    }

    /**
     * @param class-string<AbstractGenerator> $class
     */
    protected function run(string $class, ...$args): void
    {
        $generator = $this->newGenerator($class);
        assert(method_exists($generator, 'generate'));
        
        $generator->generate(...$args);
    }
}
