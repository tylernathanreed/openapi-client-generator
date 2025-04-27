<?php

namespace Reedware\OpenApi;

class Configuration
{
    public function __construct(
        public string $namespace,
        public string $src,
    ) {
    }
}
