<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\ArraySubscriber;

class ArrayTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldPaginateAnArray()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new ArraySubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = array('first', 'second');
        $view = $p->paginate($items, 1, 10);
        $this->assertInstanceOf(PaginationInterface::class, $view);

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertCount(2, $view->getItems());
        $this->assertEquals(2, $view->getTotalItemCount());
    }

    /**
     * @test
     */
    function shouldSlicePaginateAnArray()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new ArraySubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = range('a', 'u');
        $view = $p->paginate($items, 2, 10);

        $this->assertEquals(2, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertCount(10, $view->getItems());
        $this->assertEquals(21, $view->getTotalItemCount());
    }

    /**
     * @test
     */
    function shouldSupportPaginateStrategySubscriber()
    {
        $items = array('first', 'second');
        $p = new Paginator;
        $view = $p->paginate($items, 1, 10);
        $this->assertInstanceOf(PaginationInterface::class, $view);
    }

    /**
     * @test
     */
    function shouldPaginateArrayObject()
    {
        $items = array('first', 'second');
        $array = new \ArrayObject($items);
        $p = new Paginator;
        $view = $p->paginate($array, 1, 10);
        $this->assertInstanceOf(PaginationInterface::class, $view);
    }
}
