<?php

namespace Reedware\OpenApi\Schema;

use RuntimeException;
use Throwable;

/**
 * @phpstan-import-type TCompiledOperation from Specification
 */
final class OperationGroup extends AbstractSchema
{
    public function __construct(
        public readonly string $name,

        /** @var array<string,Operation> */
        public readonly array $operations,
    ) {
    }

    /** @param array<string,TCompiledOperation> $operations */
    public static function make(string $name, array $operations): static
    {
        return new self(
            name: $name,
            operations: array_map(function ($o) {
                try {
                    return Operation::make($o);
                } catch (Throwable $e) {
                    throw new RuntimeException(sprintf(
                        'Failed to generate Operation [%s]: %s',
                        $o['id'],
                        $e->getMessage(),
                    ), 0, $e);
                }
            }, $operations),
        );
    }
}
