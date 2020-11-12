<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Test\Tool\BaseTestCase;

final class PaginationInterfaceTest extends BaseTestCase
{
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new \ReflectionClass(PaginationInterface::class);
    }

    /**
     * @test
     */
    public function shouldBeCountable(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(\Countable::class));
    }

    /**
     * @test
     */
    public function shouldBeTraversable(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(\Traversable::class));
        $this->assertFalse($this->reflection->implementsInterface(\Iterator::class));
        $this->assertFalse($this->reflection->implementsInterface(\IteratorAggregate::class));
    }

    /**
     * @test
     */
    public function shouldBeArrayAccessible(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(\ArrayAccess::class));
    }
}
