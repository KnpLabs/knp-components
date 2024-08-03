<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\Pagination\PaginationInterface;
use PHPUnit\Framework\Attributes\Test;
use Test\Tool\BaseTestCase;

final class PaginationInterfaceTest extends BaseTestCase
{
    private ?\ReflectionClass $reflection = null;

    protected function setUp(): void
    {
        $this->reflection = new \ReflectionClass(PaginationInterface::class);
    }

    #[Test]
    public function shouldBeCountable(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(\Countable::class));
    }

    #[Test]
    public function shouldBeTraversable(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(\Traversable::class));
        $this->assertFalse($this->reflection->implementsInterface(\Iterator::class));
        $this->assertFalse($this->reflection->implementsInterface(\IteratorAggregate::class));
    }

    #[Test]
    public function shouldBeArrayAccessible(): void
    {
        $this->assertTrue($this->reflection->implementsInterface(\ArrayAccess::class));
    }
}
