<?php

namespace Reedware\OpenApi\Markdown;

use Stringable;

abstract class Element implements Stringable
{
    public function __toString(): string
    {
        return $this->render();
    }

    abstract public function render(): string;
}
