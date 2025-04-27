<?php

namespace Reedware\OpenApi\Markdown;

class Link extends Element
{
    public function __construct(
        public string $text,
        public string $href,
    ) {
    }

    public function render(): string
    {
        return "[{$this->text}]({$this->href})";
    }
}
