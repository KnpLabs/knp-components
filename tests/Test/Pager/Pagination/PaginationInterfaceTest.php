<?php

namespace Test\Pager\Pagination;

use ReflectionClass;
use Test\Tool\BaseTestCase;

class PaginationInterafaceTest extends BaseTestCase
{
    private $reflection;

    protected function setUp()
    {
        $this->reflection = new ReflectionClass('Knp\\Component\\Pager\\Pagination\\PaginationInterface');
    }

    /**
     * @test
     */
    function shouldBeCountable()
    {
        $this->assertTrue($this->reflection->implementsInterface('Countable'));
    }

    /**
     * @test
     */
    function shouldBeTraversable()
    {
        $this->assertTrue($this->reflection->implementsInterface('Traversable'));
        $this->assertFalse($this->reflection->implementsInterface('Iterator'));
        $this->assertFalse($this->reflection->implementsInterface('IteratorAggregate'));
    }

    /**
     * @test
     */
    function shouldBeArrayAccessible()
    {
        $this->assertTrue($this->reflection->implementsInterface('ArrayAccess'));
    }
}
