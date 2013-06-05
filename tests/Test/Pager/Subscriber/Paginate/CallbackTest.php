<?php

use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\Callback\Target;
use Knp\Component\Pager\Event\Subscriber\Paginate\Callback\CallbackSubscriber;

class CallbackTest extends BaseTestCase {

    /**
     * @test
     */
    function shouldPaginateCallbackTarget()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CallbackSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = array('item1', 'item2', 'item3');
        $target = new Target(function() use ($items) {
            return count($items);
        }, function($limit, $offset) use ($items) {
            return array_splice($items, $offset, $limit);
        });

        $view = $p->paginate($target, 2, 1);

        $this->assertEquals(2, $view->getCurrentPageNumber());
        $this->assertEquals(1, $view->getItemNumberPerPage());
        $this->assertEquals(1, count($view->getItems()));
        $this->assertEquals(3, $view->getTotalItemCount());
        $this->assertEquals(array('item2'), $view->getItems());
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    function shouldIgnoreIncorrectTarget()
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CallbackSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $p = new Paginator($dispatcher);

        $items = array('item1', 'item2', 'item3');
        $view = $p->paginate($items, 2, 1);
    }

}