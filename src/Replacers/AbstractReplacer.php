<?php

namespace Reedware\OpenApi\Replacers;

use Reedware\OpenApi\Configuration;

abstract class AbstractReplacer
{
    public function __construct(
        protected Configuration $config,
    ) {
    }
}
