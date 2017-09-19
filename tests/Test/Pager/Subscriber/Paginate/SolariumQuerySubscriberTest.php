<?php

namespace Test\Pager\Subscriber\Paginate;

use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Paginate\SolariumQuerySubscriber;

use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;

class SolariumQuerySubscriberTest extends TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage One of listeners must count and slice given target
     */
    function arrayShouldNotBeHandled()
    {
        $array = array(1 => 'foo', 2 => 'bar');

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new SolariumQuerySubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber);

        $p = new Paginator($dispatcher);
        $p->paginate($array, 1, 10);
    }
}
