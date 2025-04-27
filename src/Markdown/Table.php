<?php

namespace Reedware\OpenApi\Markdown;

class Table extends Element
{
    public function __construct(
        /** @var list<string> */
        public array $headers = [],

        /** @var list<list<?string>> */
        public array $rows = [],
        public string $empty = '*None*',
    ) {
    }

    /**
     * @param list<?string> $row
     * @return $this
     */
    public function add(array $row): static
    {
        $this->rows[] = $row;

        return $this;
    }

    public function render(): string
    {
        if (empty($this->rows)) {
            return $this->empty;
        }

        return implode("\n", [
            $this->thead(),
            $this->tdivide(),
            $this->tbody(),
        ]);
    }

    protected function thead(): string
    {
        return '| ' . implode(' | ', $this->headers) . ' |';
    }

    protected function tdivide(): string
    {
        return '| ' . implode(' | ', array_fill(0, count($this->headers), '---')) . ' |';
    }

    protected function tbody(): string
    {
        return implode("\n", array_map(fn ($r) => $this->trow($r), $this->rows));
    }

    /** @param list<?string> $row */
    protected function trow(array $row): string
    {
        return '| ' . implode(' | ', $row) . ' |';
    }
}
