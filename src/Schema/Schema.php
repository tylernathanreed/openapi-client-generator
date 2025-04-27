<?php

namespace Reedware\OpenApi\Schema;

use Attribute;
use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Markdown\Table;
use Reedware\OpenApi\Utils;
use RuntimeException;
use Throwable;

/**
 * @phpstan-import-type TSchema from Specification
 * @phpstan-import-type TDiscriminator from Specification
 * @phpstan-import-type TValue from Specification
 */
final class Schema extends AbstractSchema
{
    use Concerns\ParsesReferences;

    public function __construct(
        public readonly string $name,
        public readonly Description $description,

        /** @var list<Property> */
        public readonly array $properties,

        /** @var array<string,true> */
        public readonly array $required,
        public readonly ?string $discriminatorKey,

        /** @var ?array<string,string> */
        public readonly ?array $discriminatorMap,
        public readonly bool $nullable,
        public readonly ?string $type,

        /** @var ?list<string> */
        public readonly ?array $unionTypes,
    ) {
    }

    /** @param TSchema $schema */
    public static function make(string $name, array $schema): static
    {
        // @phpstan-ignore argument.type (not mixed)
        [$key, $map] = self::discriminator($schema['discriminator'] ?? null);

        /** @var ?list<string> $unionTypes */
        $unionTypes = isset($schema['anyOf'])
            ? array_filter(array_map(fn ($type) => self::ref($type['$ref'])[0], $schema['anyOf']))
            : null;

        /** @var array<string,true> $required */
        $required = array_fill_keys($schema['required'] ?? [], true);

        return new self(
            name: $name,
            description: new Description($schema['description'] ?? null),
            required: $required,
            properties: isset($schema['properties'])
                ? self::makeProperties($schema['properties'], $required)
                : [],
            discriminatorKey: $key,
            discriminatorMap: $map,
            type: $schema['type'] ?? null,
            unionTypes: $unionTypes,
            nullable: $schema['nullable'] ?? false,
        );
    }

    /**
     * @param array<string,TValue> $properties
     * @param array<string,true>  $required
     * @return list<Property>
     */
    protected static function makeProperties(array $properties, array $required): array
    {
        $properties = array_map(
            function ($property, $name, $i) use ($required) {
                try {
                    return Property::make($name, $i, $required[$name] ?? false, $property);
                } catch (Throwable $e) {
                    throw new RuntimeException(sprintf(
                        'Failed to generate property [%s] (%s).',
                        $name,
                        json_encode($property),
                    ), previous: $e);
                }
            },
            $properties,
            array_keys($properties),
            range(0, count($properties) - 1)
        );

        usort($properties, function (Property $a, Property $b) {
            if (($c = $b->required <=> $a->required) !== 0) {
                return $c;
            }

            if ($b->required && $a->required) {
                if (($c = ! is_null($a->default) <=> ! is_null($b->default)) !== 0) {
                    return $c;
                }
            }

            return $a->index <=> $b->index;
        });

        return $properties;
    }

    /**
     * @param  ?TDiscriminator  $discriminator
     * @return array{0:?string,1:?array<string,string>}
     */
    protected static function discriminator(?array $discriminator): array
    {
        if (is_null($discriminator)) {
            return [null, null];
        }

        $key = $discriminator['propertyName'];

        $map = $discriminator['mapping'] ?? [];

        $map = array_filter(array_map(fn ($ref) => static::ref($ref)[0], $map));

        return [$key, $map];
    }

    public function isPolymorphic(): bool
    {
        return ! is_null($this->discriminatorKey) && ! empty($this->discriminatorMap);
    }

    public function isUnionType(): bool
    {
        return ! is_null($this->unionTypes);
    }

    public function hasDateTime(): bool
    {
        foreach ($this->properties as $property) {
            if ($property->isDateTime()) {
                return true;
            }
        }

        return false;
    }

    public function hasMappedPropertyName(): bool
    {
        foreach ($this->properties as $property) {
            if ($property->requiresNameMapping()) {
                return true;
            }
        }

        return false;
    }

    /** @return list<class-string<Attribute>> */
    public function getPropertyAttributes(): array
    {
        $attributes = [];

        foreach ($this->properties as $property) {
            foreach ($property->getAttributes() as $attribute => $arguments) {
                $attributes[$attribute] = true;
            }
        }

        return array_keys($attributes);
    }

    public function getPropertiesMarkdown(): string
    {
        $table = new Table(['Property', 'Type', 'Description']);

        foreach ($this->properties as $property) {
            $type = str_replace('|', '\|', $property->getDocType() ?: $property->type ?: 'mixed');

            if ($property->typeIsRef) {
                assert(is_string($property->type));

                $type = new Link("`{$type}`", '/docs/schema/' . Utils::slug($property->type) . '.md');
            }

            if ($property->listableTypeIsRef) {
                assert(is_string($property->listableType));

                $type = new Link("`{$type}`", '/docs/schema/' . Utils::slug($property->listableType) . '.md');
            }

            if ($property->associativeTypeIsRef) {
                assert(is_string($property->associativeType));

                $type = new Link("`{$type}`", '/docs/schema/' . Utils::slug($property->associativeType) . '.md');
            }

            if (is_string($type) && strlen($type) > 40) {
                $type = str_replace('|', '|`<br/>`', $type);
            }

            $table->add([
                "`{$property->name}`",
                $type instanceof Link ? $type : "`{$type}`",
                str_replace("\n", '<br/>', (string) $property->description),
            ]);
        }

        return (string) $table;
    }
}
