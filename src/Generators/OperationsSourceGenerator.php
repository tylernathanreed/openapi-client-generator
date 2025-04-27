<?php

namespace Reedware\OpenApi\Generators;

use Reedware\OpenApi\Replacers;
use Reedware\OpenApi\Schema\OperationGroup;

/**
 * @extends AbstractSourceGenerator<OperationGroup>
 */
class OperationsSourceGenerator extends AbstractSourceGenerator
{
    /** {@inheritDoc} */
    protected $replacers = [
        Replacers\DummyNamespaceReplacer::class,
        Replacers\Operations\Source\DummyMethodsReplacer::class,
        Replacers\Operations\Source\DummyTraitReplacer::class,
        Replacers\SortImportsReplacer::class,
    ];

    public function afterAll(): void
    {
        $this->updatePerformsOperationsTrait();
    }

    protected function updatePerformsOperationsTrait(): void
    {
        $filepath = $this->basePath . '/src/PerformsOperations.php';

        $stub = $this->filesystem->get($filepath);

        if (! $stub) {
            return;
        }

        if (! preg_match('/(?P<imports>(?:^[ \/]+use [^;{]+;$\n?)+)/m', $stub, $match)) {
            return;
        }

        $traits = array_map(
            fn ($filepath) => '    use Operations\\' . basename($filepath, '.php') . ';',
            glob($this->basePath . '/src/Operations/*.php') ?: []
        );

        $stub = str_replace(rtrim($match['imports']), implode("\n", $traits), $stub);

        file_put_contents($filepath, $stub);
    }
}
