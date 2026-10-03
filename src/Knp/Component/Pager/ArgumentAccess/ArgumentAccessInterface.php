<?php

namespace Knp\Component\Pager\ArgumentAccess;

/**
 * Reads and writes the pagination arguments of the request, such as the page number or the sort field.
 */
interface ArgumentAccessInterface
{
    public function has(string $name): bool;

    public function get(string $name): string|int|float|bool|null;

    public function set(string $name, string|int|float|bool|null $value): void;
}
