<?php

namespace Reedware\OpenApi\Commands;

use Reedware\OpenApi\Enums\GeneratorType;
use Reedware\OpenApi\Exceptions\ClassGenerationException;
use Reedware\OpenApi\Exceptions\CommandFailedException;
use Override;
use Reedware\OpenApi\ClassMap;
use Reedware\OpenApi\Configuration;
use Reedware\OpenApi\Filesystem;
use Reedware\OpenApi\Generators\OpenApiGenerator;
use Reedware\OpenApi\Schema\AbstractSchema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;
use ValueError;

#[AsCommand('generate', 'Generates source files from the OpenAPI Specification')]
class GenerateCommand extends Command
{
    #[Override]
    public function handle(): int
    {
        $config = $this->bootConfig();

        $generator = new OpenApiGenerator(
            basePath: $this->basePath,
            config: $config,
            filesystem: new Filesystem,
        );

        [$type, $name] = $this->validated();

        try {
            foreach ($generator->generate($type, $name) as $response) {
                $this->{$response->type}($response->message);
            }
        } catch (ClassGenerationException $e) {
            throw new CommandFailedException($e->getMessage(), previous: $e);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            throw $e;
        }

        return 0;
    }

    protected function bootConfig(): Configuration
    {
        $config = $this->readConfig();

        ClassMap::boot($config);

        AbstractSchema::$config = $config;

        return $config;
    }

    protected function readConfig(): Configuration
    {
        $contents = file_get_contents($this->basePath . '/openapi.json');

        if ($contents === false) {
            throw new CommandFailedException('Unable to find openapi.json configuration');
        }

        $json = json_decode($contents);

        return new Configuration(
            namespace: $json->namespace,
            src: $json->src ?? 'src',
        );
    }

    /** @return array{0:?GeneratorType,1:?string} */
    protected function validated(): array
    {
        return [
            $type = $this->validatedType(),
            $this->validatedName($type),
        ];
    }

    protected function validatedType(): ?GeneratorType
    {
        /** @var ?string $type */
        $type = $this->argument('type');

        if (is_null($type)) {
            return null;
        }

        try {
            return GeneratorType::from($type);
        } catch (ValueError $e) {
            throw new CommandFailedException(sprintf(
                'Invalid type "%s". Must be one of: "%s"',
                $type,
                implode(", ", GeneratorType::values())
            ), previous: $e);
        }
    }

    protected function validatedName(?GeneratorType $type): ?string
    {
        if (is_null($type)) {
            return null;
        }

        /** @var ?string $name */
        $name = $this->argument('name');

        if (is_null($name)) {
            return null;
        }

        return $name;
    }

    /** @return list<array{0:string,1:int,2:string}> */
    protected function getArguments(): array
    {
        $types = implode("|", GeneratorType::values());

        return [
            ['type', InputArgument::OPTIONAL, "The type of source file to create ({$types})"],
            ['name', InputArgument::OPTIONAL, 'The name of the source file'],
        ];
    }
}
