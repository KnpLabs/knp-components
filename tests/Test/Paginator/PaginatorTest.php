<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginateSubscriber;

class PaginatorTest extends BaseTestCase
{
    /**
     * @test
     * @expectedException RuntimeException
     */
    function shouldNotBeAbleToPaginateWithoutListeners()
    {
        $p = new Paginator(new EventDispatcher);
        $p->paginate(array());
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    function shouldFailToPaginateUnsupportedValue()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new PaginateSubscriber);

        $p = new Paginator($dispatcher);
        $view = $p->paginate(null, 1, 10);
    }
}