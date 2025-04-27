<?php

namespace Reedware\OpenApi\Exceptions;

class MissingSpecificationException extends ClassGenerationException
{
    public function __construct(string $type, string $name)
    {
        parent::__construct("Could not find {$type} specification for [{$name}].");
    }
}
