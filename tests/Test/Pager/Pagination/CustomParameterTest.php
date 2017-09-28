<?php

use Knp\Component\Pager\ParametersResolver;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Mock\CustomParameterSubscriber;

class CustomParameterTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldGiveCustomParametersToPaginationView()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CustomParameterSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $p = new Paginator($parametersResolver, $dispatcher);

        $items = array('first', 'second');
        $view = $p->paginate($items, 1, 10);

        $this->assertEquals('val', $view->getCustomParameter('test'));
        $this->assertNull($view->getCustomParameter('nonExisting'));
    }
}
