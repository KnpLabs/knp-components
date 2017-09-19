<?php

use Knp\Component\Pager\ParametersResolver;
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
        $parametersResolver = $this->createMock(ParametersResolver::class);
        $eventDispatcher = new EventDispatcher();

        $paginator = new Paginator($parametersResolver, $eventDispatcher);
        $paginator->paginate([]);
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    function shouldFailToPaginateUnsupportedValue()
    {
        $parametersResolver = $this->createMock(ParametersResolver::class);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());

        $paginator = new Paginator($parametersResolver, $dispatcher);
        $view = $paginator->paginate(null, 1, 10);
    }
}
