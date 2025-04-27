<?php

namespace Reedware\OpenApi\Specification;

use Reedware\OpenApi\Schema\Specification;
use RuntimeException;

/**
 * @phpstan-import-type TOpenApi from Specification
 * @phpstan-type TFix array{
 *     type: 'merge'|'set',
 *     path: string,
 *     value: array<int|string,mixed>|string
 * }
 */
class SpecificationResolver
{
    /** @return TOpenApi */
    public function resolve(): array
    {
        return $this->fix($this->raw());
    }

    /**
     * @param TOpenApi $spec
     * @return TOpenApi
     */
    public function fix(array $spec): array
    {
        $path = realpath(__DIR__ . '/../../../../fixes') . '/*.json';

        $files = glob($path);

        if ($files === false) {
            return $spec;
        }

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            if ($contents === false) {
                throw new RuntimeException("Failed to open fix [{$file}].");
            }

            $fixes = json_decode($contents, true);

            assert(is_array($fixes));

            foreach ($fixes as $fix) {
                /** @var TFix $fix */
                $this->applyFix($spec, $fix);
            }
        }

        return $spec;
    }

    /**
     * @param TOpenApi $spec
     * @param TFix $fix
     * @return TOpenApi
     */
    protected function applyFix(array &$spec, array $fix): array
    {
        if ($fix['type'] === 'merge') {
            assert(is_array($fix['value']));
            // @phpstan-ignore parameterByRef.type,return.type (shape is maintained enough)
            return Fix::merge($spec, $fix['path'], $fix['value']);
        }

        // @phpstan-ignore parameterByRef.type,return.type (shape is maintained enough)
        return Fix::set($spec, $fix['path'], $fix['value']);
    }

    /** @return TOpenApi */
    protected function raw(): array
    {
        $filepath = 'https://dac-static.atlassian.com/cloud/jira/platform/swagger-v3.v3.json';

        $contents = file_get_contents($filepath);

        if ($contents === false) {
            throw new RuntimeException('Failed to open specification.');
        }

        // @phpstan-ignore return.type (Not going to validate)
        return json_decode($contents, true);
    }
}
