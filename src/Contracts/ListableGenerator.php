<?php

namespace Reedware\OpenApi\Contracts;

interface ListableGenerator
{
    public function generate(?string $name = null): void;

    /** @return list<string> */
    public function all(): array;

    /** @return list<string> */
    public function existing(): array;

    public function afterAll(): void;
}
