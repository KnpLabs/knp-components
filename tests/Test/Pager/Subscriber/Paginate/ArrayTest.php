<?php

use Knp\Component\Pager\ParametersResolver;
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
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ArraySubscriber());
        $dispatcher->addSubscriber(new MockPaginationSubscriber()); // pagination view

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $items = array('first', 'second');
        $view = $paginator->paginate($items, 1, 10);
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
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ArraySubscriber());
        $dispatcher->addSubscriber(new MockPaginationSubscriber()); // pagination view

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $items = range('a', 'u');
        $view = $paginator->paginate($items, 2, 10);

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
        $items = ['first', 'second'];

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $view = $paginator->paginate($items, 1, 10);
        $this->assertInstanceOf(PaginationInterface::class, $view);
    }

    /**
     * @test
     */
    function shouldPaginateArrayObject()
    {
        $items = ['first', 'second'];
        $array = new \ArrayObject($items);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $view = $paginator->paginate($array, 1, 10);
        $this->assertInstanceOf(PaginationInterface::class, $view);
    }
}
