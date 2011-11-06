<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\ArraySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginateSubscriber;

class ArrayTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldPaginateAnArray()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new ArraySubscriber);
        $dispatcher->addSubscriber(new PaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = array('first', 'second');
        $view = $p->paginate($items, 1, 10);
        $this->assertTrue($view instanceof PaginationInterface);

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertEquals($items, $view->getItems());
        $this->assertEquals(2, $view->getTotalItemCount());
        $this->assertEquals('', $view->getAlias());
    }

    /**
     * @test
     */
    function shouldSlicePaginateAnArray()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new ArraySubscriber);
        $dispatcher->addSubscriber(new PaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = range('a', 'u');
        $view = $p->paginate($items, 2, 10, true, 'al');

        $this->assertEquals(2, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertEquals(array_slice($items, 10, 10), $view->getItems());
        $this->assertEquals(21, $view->getTotalItemCount());
        $this->assertEquals('al', $view->getAlias());
    }

    /**
     * @test
     */
    function shouldSupportPaginateStrategySubscriber()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new PaginateSubscriber);

        $items = array('first', 'second');
        $p = new Paginator($dispatcher);
        $view = $p->paginate($items, 1, 10);
        $this->assertTrue($view instanceof PaginationInterface);
    }
}