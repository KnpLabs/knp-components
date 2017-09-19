<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Sortable\SolariumQuerySubscriber;

use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;

class SolariumQuerySubscriberTest extends TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage One of listeners must count and slice given target
     */
    function testArrayShouldNotBeHandled()
    {
        $array = array(
            'results' => array(
                0 => array(
                    'city'   => 'Lyon',
                    'market' => 'E'
                ),
                1 => array(
                    'city'   => 'Paris',
                    'market' => 'G'
                ),
            ),
            'nbTotalResults' => 2
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new SolariumQuerySubscriber());
        $dispatcher->addSubscriber(new MockPaginationSubscriber());

        $paginator = new Paginator($dispatcher);
        $paginator->paginate($array, 1, 10);
    }
}
