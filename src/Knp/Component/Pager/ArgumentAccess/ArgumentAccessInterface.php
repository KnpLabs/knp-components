<?php

namespace Knp\Component\Pager\ArgumentAccess;

interface ArgumentAccessInterface
{
    public function has(string $name): bool;

    public function get(string $name): string|int|float|bool|null;

    public function set(string $name, string|int|float|bool|null $value): void;
}
