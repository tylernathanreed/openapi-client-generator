<?php

namespace Reedware\OpenApi\Schema;

use Attribute;
use Closure;
use Reedware\OpenApi\ClassMap;
use Reedware\OpenApi\Client\Http\Attributes\MapName;
use Reedware\OpenApi\Client\Http\Attributes\PolymorphicList;
use Reedware\OpenApi\Client\Http\PolymorphicDto;
use RuntimeException;
use Stringable;

/**
 * @phpstan-import-type TValue from Specification
 * @phpstan-import-type TAdditionalProperties from Specification
 */
final class Property extends AbstractSchema implements Stringable
{
    use Concerns\ParsesReferences;
    use Concerns\ResolvesSafeNames;

    /** @var array<string,mixed> */
    protected array $computed = [];

    public function __construct(
        public readonly string $name,
        public readonly int $index,
        public readonly Description $description,
        public readonly mixed $example = null,
        public readonly ?string $type = null,
        public readonly bool $typeIsRef = false,
        public readonly ?string $format = null,
        public readonly ?string $listableType = null,
        public readonly ?bool $listableTypeIsRef = null,
        public readonly ?string $associativeType = null,
        public readonly ?bool $associativeTypeIsRef = null,
        public readonly int|string|bool|null $default = null,
        public readonly bool $required = false,
        public readonly bool $readOnly = false,
        public readonly bool $writeOnly = false,
        public readonly ?int $minItems = null,
        public readonly ?int $maxItems = null,
        public readonly bool $uniqueItems = false,

        /** @var ?list<string> */
        public readonly ?array $enum = null,
    ) {
    }

    /** @param TValue $schema */
    public static function make(string $name, int $index, bool $required, array $schema): static
    {
        $type = $schema['$ref']
            ?? $schema['allOf'][0]['$ref']
            ?? $schema['type']
            ?? null;

        [$nativeType, $isTypeRef] = self::ref($type);

        $listableType = $schema['items']['type'] ?? $schema['items']['$ref'] ?? null;

        [$nativeListableType, $isListableTypeRef] = self::ref($listableType);

        [$associativeType, $isAssociativeTypeRef] = self::associativeType($schema['additionalProperties'] ?? null);

        return new self(
            name: $name,
            index: $index,
            required: $required,
            description: new Description($schema['description'] ?? null),
            example: $schema['example'] ?? null,
            type: $nativeType,
            typeIsRef: $isTypeRef ?? false,
            format: $schema['format'] ?? null,
            listableType: $nativeListableType ?? 'mixed',
            listableTypeIsRef: $isListableTypeRef ?? null,
            associativeType: $associativeType,
            associativeTypeIsRef: $isAssociativeTypeRef,
            default: $schema['default'] ?? null,
            readOnly: $schema['readOnly'] ?? false,
            writeOnly: $schema['writeOnly'] ?? false,
            minItems: $schema['minItems'] ?? null,
            maxItems: $schema['maxItems'] ?? null,
            uniqueItems: $schema['uniqueItems'] ?? false,
            enum: $schema['enum'] ?? null,
        );
    }

    /**
     * @param ?TAdditionalProperties $type
     * @return array{0:?string,1:?bool}
     */
    protected static function associativeType(array|bool|null $type): array
    {
        if (is_null($type) || ! is_array($type)) {
            return [null, null];
        }

        if (isset($type['$ref'])) {
            return static::ref($type['$ref']);
        }

        if (isset($type['items']['$ref'])) {
            return static::ref($type['items']['$ref']);
        }

        if (isset($type['items']['type'])) {
            return ['list<' . $type['items']['type'] . '>', false];
        }

        if (isset($type['type'])) {
            return [match ($type['type']) {
                'integer' => 'int',
                'boolean' => 'bool',
                default => $type['type'],
            }, false];
        }

        return [null, null];
    }

    public function hasType(): bool
    {
        return ! is_null($this->type);
    }

    public function hasSimpleType(): bool
    {
        return in_array($this->type, ['bool', 'int', 'float'])
            || (
                $this->type === 'string' &&
                ! $this->isEnum()
            )
            || (
                $this->type === 'object' &&
                ! $this->isAssociativeArray()
            );
    }

    public function hasRefType(): bool
    {
        return $this->typeIsRef;
    }

    public function isEnum(): bool
    {
        return $this->type === 'string' && ! empty($this->enum);
    }

    public function isArray(): bool
    {
        return $this->type === 'array';
    }

    public function isAssociativeArray(): bool
    {
        return $this->type === 'object';
    }

    public function isDateTime(): bool
    {
        return $this->type === 'string' && $this->format === 'date-time';
    }

    public function getSafeName(): string
    {
        return $this->compute('safeName', fn () => static::resolveSafeName($this->name));
    }

    public function getOriginalName(): string
    {
        return $this->name;
    }

    public function requiresNameMapping(): bool
    {
        return $this->getSafeName() != $this->getOriginalName();
    }

    public function getNativeType(): string
    {
        return match ($this->type) {
            'number' => 'float',
            'integer' => 'int',
            'boolean' => 'bool',
            'object' => $this->isAssociativeArray() ? 'array' : 'object',
            'string' => $this->isDateTime() ? 'DateTimeImmutable' : 'string',
            null => 'mixed',
            default => $this->type,
        };
    }

    public function getDocType(): ?string
    {
        return $this->compute('docType', fn () => $this->resolveDocType());
    }

    protected function resolveDocType(): ?string
    {
        if (! $this->hasType() || $this->hasSimpleType() || $this->hasRefType()) {
            return null;
        }

        if ($this->isEnum()) {
            return '\'' . implode('\'|\'', $this->enum) . '\'' . ($this->required ? '' : '|null');
        }

        if ($this->isArray()) {
            $type = $this->listableType;

            if ($this->listableTypeIsRef) {
                $schema = Specification::getComponentSchema($this->listableType);
                
                if ($schema->isPolymorphic()) {
                    $qualifiedMorphTypes = array_values($schema->discriminatorMap);
                    $baseMorphTypes = array_map(fn ($c) => class_basename($c), $qualifiedMorphTypes);
                    $type = implode('|', $baseMorphTypes);
                }
            }

            return ($this->required ? '' : '?') . "list<{$type}>";
        }

        if ($this->isAssociativeArray()) {
            return 'array<string,' . ($this->associativeType ?: 'mixed') . '>';
        }

        throw new RuntimeException(
            'Unable to generate doc type for property: ' . json_encode($this)
        );
    }

    public function hasDocType(): bool
    {
        return ! is_null($this->getDocType());
    }

    public function getDefaultString(): string
    {
        if (is_null($this->default)) {
            return 'null';
        }

        if (is_string($this->default)) {
            return "'{$this->default}'";
        }

        if (is_bool($this->default) || $this->type === 'bool') {
            return $this->default ? 'true' : 'false';
        }

        return (string) $this->default;
    }

    public function getDoc(): ?string
    {
        $tags = [];

        if ($docType = $this->getDocType()) {
            $tags[] = ['var', $docType];
        }

        if ($this->example) {
            $example = is_string($this->example)
                ? "'{$this->example}'"
                : preg_replace(
                    pattern: ["/\n/", '/array \( */', '/ +/', '/, ?\)/'],
                    replacement: ['', '[', ' ', ']'],
                    // @phpstan-ignore argument.type (several assumptions)
                    subject: var_export(json_decode(json_encode($this->example), true), true)
                );

            if (! is_null($example)) {
                $tags[] = ['example', $example];
            }
        }

        return $this->description->render(
            indent: 8,
            tags: $tags,
        );
    }

    public function getDefinition(): string
    {
        $indent = str_repeat(' ', 8);

        return strtr('{indent}public {nullable}{phpType} ${name}{default},', [
            '{indent}' => $indent,
            '{nullable}' => $this->required || is_null($this->type) ? '' : '?',
            '{phpType}' => $this->getNativeType(),
            '{name}' => $this->getSafeName(),
            '{default}' => ! is_null($this->default)
                ? " = {$this->getDefaultString()}"
                : ($this->required ? '' : ' = null'),
        ]);
    }

    /** @return array<class-string<Attribute>,list<scalar>> */
    public function getAttributes(): array
    {
        $attributes = [];

        if ($this->requiresNameMapping()) {
            $attributes[ClassMap::resolve(MapName::class)] = [$this->getOriginalName()];
        }

        if ($this->listableType && $this->listableTypeIsRef) {
            $schema = Specification::getComponentSchema($this->listableType);

            if ($schema->isPolymorphic()) {
                $attributes[ClassMap::resolve(PolymorphicList::class)] = [$this->listableType . '::class'];
            }
        }

        // @phpstan-ignore return.type (semantics)
        return $attributes;
    }

    protected function getAttributesDefinition(): string
    {
        $indent = str_repeat(' ', 8);

        $attributes = $this->getAttributes();

        $content = '';

        foreach ($attributes as $attribute => $arguments) {
            $name = class_basename($attribute);

            $argString = '';

            foreach ($arguments as $argument) {
                $argString .= str_ends_with((string) $argument, '::class')
                    ? $argument . ', '
                    : "'{$argument}', ";
            }

            if (! empty($argString)) {
                $argString = '(' . rtrim($argString, ', ') . ')';
            }

            $content .= "{$indent}#[{$name}{$argString}]\n";
        }

        if (! empty($content)) {
            $content = "{$content}";
        }

        return $content;
    }

    /**
     * @phpstan-template T
     *
     * @param Closure():T $callback
     *
     * @return T
     */
    protected function compute(string $key, Closure $callback): mixed
    {
        if (array_key_exists($key, $this->computed)) {
            return $this->computed[$key];
        }

        return $this->computed[$key] = $callback();
    }

    public function __toString(): string
    {
        return $this->getDoc() . $this->getAttributesDefinition() . $this->getDefinition();
    }
}
