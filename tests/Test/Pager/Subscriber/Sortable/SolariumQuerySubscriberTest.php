<?php

namespace Test\Pager\Subscriber\Sortable;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\Subscriber\Sortable\SolariumQuerySubscriber;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Tool\BaseTestCase;

final class SolariumQuerySubscriberTest extends BaseTestCase
{
    #[Test]
    public function testArrayShouldNotBeHandled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('One of listeners must count and slice given target');

        $array = [
            'results' => [
                0 => [
                    'city'   => 'Lyon',
                    'market' => 'E',
                ],
                1 => [
                    'city'   => 'Paris',
                    'market' => 'G',
                ],
            ],
            'nbTotalResults' => 2,
        ];

        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $dispatcher = new EventDispatcher();

        $dispatcher->addSubscriber(new SolariumQuerySubscriber($accessor));
        $dispatcher->addSubscriber(new MockPaginationSubscriber());

        $paginator = new Paginator($dispatcher, $accessor);
        $paginator->paginate($array, 1, 10);
    }
}
