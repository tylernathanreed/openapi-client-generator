<?php

namespace Reedware\OpenApi\Schema;

use Reedware\OpenApi\Markdown\Link;
use Reedware\OpenApi\Markdown\Table;
use Reedware\OpenApi\Utils;
use Stringable;

/**
 * @phpstan-import-type TCompiledOperation from Specification
 * @phpstan-import-type TParameter from Specification
 */
final class Operation extends AbstractSchema implements Stringable
{
    use Concerns\ParsesReferences;

    public function __construct(
        public readonly string $id,
        public readonly string $uri,
        public readonly string $method,
        public readonly string $group,
        public readonly Description $description,
        public readonly string $summary,
        public readonly int $successCode,

        /** @var array{0:string}|string|true */
        public readonly array|string|bool $successSchema,
        public readonly ?string $successExample = null,
        public readonly ?string $bodySchema = null,

        /** @var array<string,mixed> */
        public readonly ?array $bodyExample = null,

        /** @var list<Parameter> */
        public readonly array $parameters = [],
        public readonly bool $deprecated = false,
    ) {
    }

    /** @param TCompiledOperation $operation */
    public static function make(array $operation): static
    {
        $op = $operation['operation'];

        $responseCodes = array_keys($op['responses'] ?? []);

        $successCode = count($responseCodes) > 1
            ? min(...$responseCodes)
            : $responseCodes[0];

        $sucessResponse = $op['responses'][$successCode]['content']['application/json'] ?? [];

        $successSchema = $sucessResponse['schema'] ?? null;
        $successExample = ! is_null($example = ($sucessResponse['example'] ?? null))
            ? (is_string($example) ? $example : (json_encode($example) ?: null))
            : null;

        if (isset($successSchema['$ref'])) {
            $successSchema = self::ref($successSchema['$ref'])[0] ?? true;
        } elseif (isset($successSchema['items']['$ref'])) {
            $successSchema = [self::ref($successSchema['items']['$ref'])[0]];
            assert(! is_null($successSchema[0]));
        } else {
            $successSchema = true;
        }

        $body = $op['requestBody']['content']['application/json'] ?? [];

        $bodySchema = self::ref($body['schema']['$ref'] ?? null)[0] ?? null;
        $bodyExample = ! is_null($example = ($body['example'] ?? null))
            ? (is_string($example) ? json_decode($example, true) : $example)
            : null;

        assert(is_array($bodyExample) || is_null($bodyExample));
        /** @var ?array<string,mixed> $bodyExample */

        return new self(
            id: $operation['id'],
            uri: $operation['uri'],
            method: $operation['method'],
            group: $operation['group'],
            description: new Description($op['description'] ?? null),
            summary: ucwords($op['summary'] ?? $operation['id']),
            deprecated: $op['deprecated'] ?? false,
            successCode: $successCode,
            successSchema: $successSchema,
            successExample: $successExample,
            bodySchema: $bodySchema,
            bodyExample: $bodyExample,
            parameters: isset($op['parameters'])
                ? self::makeParameters($op['parameters'])
                : [],
        );
    }

    /**
     * @param list<TParameter> $parameters
     * @return list<Parameter>
     */
    protected static function makeParameters(array $parameters): array
    {
        $parameters = array_map(
            fn ($param, $i) => Parameter::make($i, $param),
            $parameters,
            array_keys($parameters)
        );

        usort($parameters, function (Parameter $a, Parameter $b) {
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

        return $parameters;
    }

    public function getDoc(): ?string
    {
        $tags = [];

        foreach ($this->parameters as $param) {
            $tags[] = ['param', $param->getDoc()];
        }

        if (is_array($this->successSchema)) {
            $tags[] = ['return', 'list<Schema\\' . $this->successSchema[0] . '>'];
        }

        return $this->description->render(
            indent: 4,
            tags: $tags,
        );
    }

    public function getDefinition(): string
    {
        $returnType = $this->successSchema === true
            ? 'bool'
            : (
                is_array($this->successSchema)
                    ? 'array'
                    : "Schema\\{$this->successSchema}"
            );

        $schema = $this->successSchema === true
            ? 'true'
            : (
                is_array($this->successSchema)
                    ? "[Schema\\{$this->successSchema[0]}::class]"
                    : "{$returnType}::class"
            );

        $parameters = [];

        if ($this->bodySchema) {
            $parameters[] = '        Schema\\' . $this->bodySchema . ' $request,';

            $bodyParam = '$request';
        }

        foreach ($this->parameters as $param) {
            $parameters[] = $param->getDefinition();
        }

        $queryParam = $this->getCallParamString('query');
        $pathParam = $this->getCallParamString('path');
        $headerParam = $this->getCallParamString('header');

        $arguments = array_filter([
            ['uri', "'{$this->uri}'"],
            ['method', "'{$this->method}'"],
            ['body', $bodyParam ?? null],
            ['header', $headerParam ?? null],
            ['query', $queryParam ?? null],
            ['path', $pathParam ?? null],
            ['success', $this->successCode],
            ['schema', $schema],
        ], fn ($arg) => ! empty($arg[1]));

        $indent = str_repeat(' ', 12);
        $argString = implode("\n", array_map(fn ($arg) => "{$indent}{$arg[0]}: {$arg[1]},", $arguments));

        $indent = str_repeat(' ', 8);
        $paramString = implode("\n", $parameters);

        if (! empty($paramString)) {
            $paramString = "\n" . $paramString . "\n    ";
            $returnType .= ' ';
        } else {
            $returnType = rtrim($returnType) . "\n    ";
        }

        return <<<CODE
            public function {$this->getSafeId()}({$paramString}): {$returnType}{
                return \$this->call(
        {$argString}
                );
            }
        CODE;
    }

    protected function getCallParamString(string $location): ?string
    {
        $compact = [];
        $append = [];

        foreach ($this->parameters as $param) {
            if ($param->location !== $location) {
                continue;
            }

            $safeName = $param->getSafeName();

            if ($param->name === $safeName) {
                $compact[] = $safeName;
            } else {
                $append[] = [$param->name, $safeName];
            }
        }

        if (empty($compact) && empty($append)) {
            return null;
        }

        $compactStr = ! empty($compact)
            ? 'compact(\'' . implode('\', \'', $compact) . '\')'
            : null;

        $appendStr = ! empty($append)
            ? '[' . implode(', ', array_map(fn ($a) => "'{$a[0]}' => \${$a[1]}", $append)) . ']'
            : null;

        if (empty($compactStr)) {
            return $appendStr;
        }

        if (empty($appendStr)) {
            return $compactStr;
        }

        return '[...' . $compactStr . ', ...(' . $appendStr . ')]';
    }

    public function getSafeId(): string
    {
        $id = str_replace([
            '_get',
            '_post',
            '_put',
            '_patch',
            '_delete',
        ], '', $this->id);

        if (($position = strpos($id, '.')) !== false) {
            $id = substr($id, $position + 1);
        }

        return lcfirst($id);
    }

    public function __toString(): string
    {
        return $this->getDoc() . $this->getDefinition();
    }

    public function getTestDefinition(): string
    {
        $returnType = $this->successSchema === true
            ? 'true'
            : (
                is_array($this->successSchema)
                ? 'array'
                : "Schema\\{$this->successSchema}"
            );

        $schema = $this->successSchema === true
            ? $returnType
            : (
                is_array($this->successSchema)
                ? "[Schema\\{$this->successSchema[0]}::class]"
                : "{$returnType}::class"
            );

        $parameters = [];

        if ($this->bodySchema) {
            $parameters[] = '        Schema\\' . $this->bodySchema . ' $request,';

            $bodyParam = '$request';

            $argString = "\n" . str_repeat(' ', 16) . "\$request,\n" . str_repeat(' ', 12);

            $setupStr = "\n" . str_repeat(' ', 8) . "\$request = \$this->deserialize(Schema\\{$this->bodySchema}::class, [\n";

            $escape = function ($value) use (&$escape): string|array {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        /** @var scalar|array<string,mixed> $v */
                        $value[$k] = $escape($v);
                    }

                    return $value;
                }

                /** @var scalar $value */
                return str_replace('\'', '\\\'', (string) $value);
            };

            foreach ((array) $this->bodyExample as $key => $value) {
                if (is_null($value)) {
                    $value = 'null';
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $isList = array_is_list($value);

                    $value = $escape($value);

                    $value = str_replace(
                        search: ['array (', ')', " => \n", '  ', "\n"],
                        replace: ['[', ']', ' => ', '    ', "\n" . str_repeat(' ', 12)],
                        subject: var_export($value, true)
                    );

                    $value = preg_replace("/ => +\[/", ' => [', $value) ?: '';

                    if ($isList) {
                        $value = preg_replace("/\d+ => /", '', $value);
                    }
                } else {
                    // @phpstan-ignore cast.string (mixed to string)
                    $value = (string) $value;

                    $value = $escape($value);

                    assert(is_string($value));

                    $value = '\'' . $value . '\'';
                }

                $setupStr .= str_repeat(' ', 12) . "'{$key}' => {$value},\n";
            }

            $setupStr .= str_repeat(' ', 8) . "]);\n";

            if (empty($this->bodyExample)) {
                $setupStr = strtr("\n{indent}\$this->markTestIncomplete(\n{indent2}'{reason}'\n{indent});\n", [
                    '{indent}' => str_repeat(' ', 8),
                    '{indent2}' => str_repeat(' ', 12),
                    '{reason}' => 'Missing body example.',
                ]);
            }
        }

        if (! empty($this->parameters) && isset($argString)) {
            $argString = rtrim($argString);
        } elseif (! isset($argString)) {
            $argString = '';
        }

        foreach ($this->parameters as $param) {
            $parameters[] = $param->getDefinition();

            $argString .= "\n" . str_repeat(' ', 16) . "\${$param->getSafeName()},";

            $setupStr = ($setupStr ?? '') . "\n" . str_repeat(' ', 8) . $param->getAssignment();
        }

        if (! empty($this->parameters) && isset($setupStr)) {
            $argString .= "\n" . str_repeat(' ', 12);
            $setupStr .= "\n";
        }

        $queryParam = $this->getCallParamString('query');
        $pathParam = $this->getCallParamString('path');
        $headerParam = $this->getCallParamString('header');

        $arguments = array_filter([
            ['uri', "'{$this->uri}'"],
            ['method', "'{$this->method}'"],
            ['body', $bodyParam ?? null],
            ['header', $headerParam ?? null],
            ['query', $queryParam ?? null],
            ['path', $pathParam ?? null],
            ['success', $this->successCode],
            ['schema', $schema],
        ], fn ($arg) => ! empty($arg[1]));

        $indent = str_repeat(' ', 16);
        $callString = implode("\n", array_map(fn ($arg) => "{$indent}'{$arg[0]}' => {$arg[1]},", $arguments));

        $indent = str_repeat(' ', 8);
        $paramString = implode("\n", $parameters);

        if (! empty($paramString)) {
            $paramString = "\n" . $paramString . "\n    ";
            $returnType .= ' ';
        } else {
            $returnType = rtrim($returnType) . "\n    ";
        }

        $setupStr ??= '';

        $testMethod = 'test' . ucfirst($this->getSafeId());

        $response = ! empty($this->successExample)
            ? '\'' . str_replace('\'', '\\\'', $this->successExample) . '\''
            : 'null';

        if (is_null($this->successExample) && $this->successCode !== 204) {
            $setupStr = strtr("\n{indent}\$this->markTestIncomplete(\n{indent2}'{reason}'\n{indent});\n", [
                '{indent}' => str_repeat(' ', 8),
                '{indent2}' => str_repeat(' ', 12),
                '{reason}' => 'Missing response example.',
            ]);
        }

        return <<<CODE
            public function {$testMethod}(): void
            {{$setupStr}
                \$this->assertCall(
                    method: '{$this->getSafeId()}',
                    call: [
        {$callString}
                    ],
                    arguments: [{$argString}],
                    response: {$response},
                );
            }
        CODE;
    }

    public function toMarkdown(): string
    {
        $markdown = '## ' . $this->summary . "\n";

        $markdown .= '<a name="' . $this->id . '"></a>' . "\n\n";

        $markdown .= 'Official Documentation: ' . $this->getDocumentationLink() . "\n\n";

        $markdown .= $this->description->toMarkdown() . "\n\n";

        if (! empty($example = $this->getExampleMarkdown())) {
            $markdown .= "### Example\n\n{$example}\n";
        }

        if (! empty($this->bodySchema) || ! empty($this->successSchema)) {
            $markdown .= "\n### Request\n\n";
        }

        if (! empty($this->bodySchema)) {
            $markdown .= "#### Request Body\n\n";

            $namespace = static::$config->namespace;

            $markdown .= 'Source: ' . new Link(
                "`{$namespace}\Schema\\{$this->bodySchema}`",
                '/docs/schema/' . Utils::slug($this->bodySchema) . '.md',
            ) . "\n\n";

            $schema = Specification::getComponentSchema($this->bodySchema);

            if ($schema->description->description) {
                $markdown .= $schema->description->toMarkdown() . "\n\n";
            }

            if (! empty($properties = $schema->getPropertiesMarkdown())) {
                $markdown .= $properties . "\n\n";
            }
        }

        if (! empty($this->parameters)) {
            $markdown .= "#### Query Parameters\n\n";

            $markdown .= $this->getParametersMarkdown() . "\n\n";
        }

        if (! empty($this->successSchema)) {
            $markdown .= "#### Response\n\n";

            if ($this->successSchema === true) {
                $markdown .= '`true`';
            } elseif (is_string($this->successSchema)) {
                $namespace = static::$config->namespace;

                $markdown .= 'Source: ' . new Link(
                    "`{$namespace}\Schema\\{$this->successSchema}`",
                    '/docs/schema/' . Utils::slug($this->successSchema) . '.md',
                ) . "\n\n";

                $schema = Specification::getComponentSchema($this->successSchema);

                if ($schema->description->description) {
                    $markdown .= $schema->description->toMarkdown() . "\n\n";
                }

                if (! empty($properties = $schema->getPropertiesMarkdown())) {
                    $markdown .= $properties . "\n\n";
                }
            }
        }

        return $markdown;
    }

    protected function getExampleMarkdown(): ?string
    {
        $returnType = $this->successSchema === true
            ? 'true'
            : (
                is_array($this->successSchema)
                ? 'array'
                : "Schema\\{$this->successSchema}"
            );

        $parameters = [];

        if ($this->bodySchema) {
            $parameters[] = '        Schema\\' . $this->bodySchema . ' $request,';

            $argString = empty($this->parameters)
                ? "new Schema\\{$this->bodySchema}(\n"
                : "\n    request: new Schema\\{$this->bodySchema}(\n";

            $namespace = static::$config->namespace;

            $setupStr = "\nuse {$namespace}\Schema;\n";

            $escape = function ($value) use (&$escape): string|array {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        /** @var scalar|array<string,mixed> $v */
                        $value[$k] = $escape($v);
                    }

                    return $value;
                }

                /** @var scalar $value */
                return str_replace('\'', '\\\'', (string) $value);
            };

            foreach ((array) $this->bodyExample as $key => $value) {
                if (is_null($value)) {
                    $value = 'null';
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $isList = array_is_list($value);

                    $value = $escape($value);

                    $value = str_replace(
                        search: ['array (', ')', " => \n", '  ', "\n"],
                        replace: ['[', ']', ' => ', '    ', "\n" . str_repeat(' ', 12)],
                        subject: var_export($value, true)
                    );

                    $value = preg_replace("/ => +\[/", ' => [', $value) ?: '';

                    if ($isList) {
                        $value = preg_replace("/\d+ => /", '', $value);
                    }
                } else {
                    // @phpstan-ignore cast.string (mixed to string)
                    $value = (string) $value;

                    $value = $escape($value);

                    assert(is_string($value));

                    $value = '\'' . $value . '\'';
                }

                $argString .= str_repeat(' ', empty($this->parameters) ? 4 : 8) . "{$key}: {$value},\n";
            }

            $argString .= empty($this->parameters)
                ? ')'
                : str_repeat(' ', 4) . ")\n";

            if (empty($this->bodyExample)) {
                return null;
            }
        }

        if (! empty($this->parameters) && isset($argString)) {
            $argString = rtrim($argString);
        } elseif (! isset($argString)) {
            $argString = '';
        }

        foreach ($this->parameters as $param) {
            $parameters[] = $param->getDefinition();

            $argString .= "\n" . str_repeat(' ', 4) . "{$param->getSafeName()}: {$param->getAssignmentValue()},";
        }

        if (! empty($this->parameters)) {
            $argString .= "\n";
        }

        $setupStr ??= '';

        if (is_null($this->successExample) && $this->successCode !== 204) {
            return null;
        }

        return <<<CODE
        ```php{$setupStr}
        /** @var {$returnType} \$response */
        \$response = \$client->{$this->id}({$argString});
        ```
        CODE;
    }

    protected function getParametersMarkdown(): string
    {
        $table = new Table(['Property', 'Type', 'Description']);

        foreach ($this->parameters as $parameter) {
            $type = str_replace('|', '\|', $parameter->getDocType() ?: (
                ($parameter->required ? '' : '?') .
                $parameter->type
            ) ?: 'mixed');

            if ($parameter->typeIsRef) {
                assert(is_string($parameter->type));

                $type = new Link("`{$type}`", '/docs/schema/' . Utils::slug($parameter->type) . '.md');
            }

            if ($parameter->listableTypeIsRef) {
                assert(is_string($parameter->listableType));

                $type = new Link("`{$type}`", '/docs/schema/' . Utils::slug($parameter->listableType) . '.md');
            }

            if ($parameter->associativeTypeIsRef) {
                assert(is_string($parameter->associativeType));

                $type = new Link("`{$type}`", '/docs/schema/' . Utils::slug($parameter->associativeType) . '.md');
            }

            if (is_string($type) && strlen($type) > 40) {
                $type = str_replace('|', '|`<br/>`', $type);
            }

            $table->add([
                "`{$parameter->name}`",
                $type instanceof Link ? $type : "`{$type}`",
                str_replace("\n", '<br/>', (string) $parameter->description),
            ]);
        }

        return (string) $table;
    }

    public function getDocumentationLink(): string
    {
        return sprintf(
            'https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-%s/#api-%s-%s',
            Utils::slug($this->group),
            Utils::slug(ltrim($this->uri, '/')),
            $this->method,
        );
    }
}
