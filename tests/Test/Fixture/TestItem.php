<?php

namespace Test\Fixture;

class TestItem
{
    /**
     * @var int
     */
    private $sortProperty;

    public function __construct(int $sortProperty)
    {
        $this->sortProperty = $sortProperty;
    }

    public function getSortProperty(): int
    {
        return $this->sortProperty;
    }
}
