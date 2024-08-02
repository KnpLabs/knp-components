<?php

namespace Test\Fixture;

class TestItem
{
    public function __construct(private readonly int $sortProperty)
    {
    }

    public function getSortProperty(): int
    {
        return $this->sortProperty;
    }
}
