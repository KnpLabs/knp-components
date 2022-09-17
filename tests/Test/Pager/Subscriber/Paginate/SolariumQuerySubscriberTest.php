<?php

namespace Test\Pager\Subscriber\Paginate;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\SolariumQuerySubscriber;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;

final class SolariumQuerySubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function arrayShouldNotBeHandled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('One of listeners must count and slice given target');

        $array = [1 => 'foo', 2 => 'bar'];

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new SolariumQuerySubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber);

        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $p = new Paginator($dispatcher, $accessor);
        $p->paginate($array, 1, 10);
    }
}
