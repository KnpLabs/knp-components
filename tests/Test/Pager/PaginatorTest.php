<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;

class PaginatorTest extends BaseTestCase
{
    /**
     * @test
     * @expectedException RuntimeException
     */
    function shouldNotBeAbleToPaginateWithoutListeners()
    {
        $paginator = new Paginator(new EventDispatcher());
        $paginator->paginate(array());
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    function shouldFailToPaginateUnsupportedValue()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());

        $paginator = new Paginator($dispatcher);
        $view = $paginator->paginate(null, 1, 10);
    }
}