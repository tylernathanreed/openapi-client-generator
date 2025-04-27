<?php

namespace Reedware\OpenApi\Schema;

use Stringable;

/**
 * @phpstan-type TDocTag array{0:?string,1:string}
 */
final class Description extends AbstractSchema implements Stringable
{
    public function __construct(
        public readonly ?string $description
    ) {
    }

    /** @param list<TDocTag> $tags */
    public function render(int $indent = 0, array $tags = []): ?string
    {
        if (empty($this->description) && empty($tags)) {
            return null;
        }

        $indent = str_repeat(' ', $indent);

        [$lines, $links] = $this->build();

        $content = [
            ...array_map(fn ($line) => [null, $line], $lines),
            ...array_map(fn ($line) => ['link', $line], $links),
            ...$tags,
        ];

        if (empty($content)) {
            return null;
        }

        $doc = [];
        $previous = $content[0][0];

        foreach ($content as $line) {
            [$tag, $value] = $line;

            if ($tag != $previous) {
                $doc[] = '';
                $previous = $tag;
            }

            $doc[] = $tag
                ? "@{$tag} {$value}"
                : $value;
        }

        if (count($doc) === 1) {
            return "{$indent}/** {$doc[0]} */\n";
        }

        return $indent . implode("\n" . $indent, [
            '/**',
            ...array_map(fn ($d) => " * {$d}", $doc),
            " */\n",
        ]);
    }

    public function toMarkdown(): ?string
    {
        [$lines, $links] = $this->build();

        if (empty($lines)) {
            return null;
        }

        $links = array_map(fn ($link) => 'See: ' . $link, $links);

        return implode("\n", [
            ...$lines,
            ...$links,
        ]) ;
    }

    /** @return array{0:list<string>,1:list<string>} */
    protected function build(): array
    {
        if (is_null($this->description)) {
            return [[], []];
        }

        [$description, $links] = $this->extractLinks($this->description);

        $description = preg_replace(
            ['/\.?(\n+)/', '/\. /', '/ \*  /', '/\*\//'],
            ['$1', ".\n", ' - ', '* /'],
            $description
        );

        assert(is_string($description));

        $lines = explode("\n", rtrim($description, "\n"));

        return [$lines, $links];
    }

    /** @return array{0:string,1:list<string>} */
    protected function extractLinks(string $description): array
    {
        $result = preg_match_all('/\[(?<label>[^\]]+)\]\((?<link>[^\)]+)\)/', $description, $matches);

        if (! $result) {
            return [$description, []];
        }

        $links = array_combine($matches['label'], $matches['link']);

        foreach ($links as $label => $link) {
            $description = str_replace("[{$label}]({$link})", "\"{$label}\"", $description);
        }

        $links = array_filter($links, fn ($link) => ! str_starts_with($link, '#'));

        return [$description, array_values($links)];
    }

    public function __toString(): string
    {
        return $this->description ?: '';
    }
}
