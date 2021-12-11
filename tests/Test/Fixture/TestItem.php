<?php

namespace Test\Fixture;

class TestItem
{
    private int $sortProperty;

    public function __construct(int $sortProperty)
    {
        $this->sortProperty = $sortProperty;
    }

    public function getSortProperty(): int
    {
        return $this->sortProperty;
    }
}
