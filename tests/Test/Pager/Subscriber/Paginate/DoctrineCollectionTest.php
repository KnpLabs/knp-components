<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\DoctrineCollectionSubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Doctrine\Common\Collections\ArrayCollection;

class DoctrineCollectionTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldPaginateCollection()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new DoctrineCollectionSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = new ArrayCollection(array('first', 'second'));
        $view = $p->paginate($items, 1, 10);
        $this->assertTrue($view instanceof PaginationInterface);

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertEquals(2, count($view->getItems()));
        $this->assertEquals(2, $view->getTotalItemCount());
        $this->assertEquals('', $view->getAlias());
    }

    /**
     * @test
     */
    function shouldSlicePaginateAnArray()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new DoctrineCollectionSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = new ArrayCollection(range('a', 'u'));
        $view = $p->paginate($items, 2, 10, array('alias' => 'al'));

        $this->assertEquals(2, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertEquals(10, count($view->getItems()));
        $this->assertEquals(21, $view->getTotalItemCount());
        $this->assertEquals('al', $view->getAlias());
    }

    /**
     * @test
     */
    function shouldSupportPaginateStrategySubscriber()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new DoctrineCollectionSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view

        $items = new ArrayCollection(array('first', 'second'));
        $p = new Paginator($dispatcher);
        $view = $p->paginate($items, 1, 10);
        $this->assertTrue($view instanceof PaginationInterface);
    }
}
