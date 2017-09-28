<?php

use Knp\Component\Pager\ParametersResolver;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\CollectionSubscriber;
use Doctrine\Common\Collections\ArrayCollection;

class CollectionTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldPaginateCollection()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new CollectionSubscriber());
        $dispatcher->addSubscriber(new MockPaginationSubscriber()); // pagination view

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $items = new ArrayCollection(array('first', 'second'));
        $view = $paginator->paginate($items, 1, 10);

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
        $dispatcher->addSubscriber(new CollectionSubscriber());
        $dispatcher->addSubscriber(new MockPaginationSubscriber()); // pagination view

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $items = new ArrayCollection(range('a', 'u'));
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
        $items = new ArrayCollection(['first', 'second']);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $view = $paginator->paginate($items, 1, 10);
        $this->assertInstanceOf(PaginationInterface::class, $view);
    }
}
