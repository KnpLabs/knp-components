<?php

namespace Test\Pager\Subscriber\Paginate;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\BeforeEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\Tool\BaseTestCase;

final class PaginationSubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldRegisterExpectedSubscribersOnlyOnce(): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $dispatcher->expects($this->exactly(12))->method('addSubscriber');

        $subscriber = new PaginationSubscriber;

        $accessor = $this->createMock(ArgumentAccessInterface::class);

        $beforeEvent = new BeforeEvent($dispatcher, $accessor);
        $subscriber->before($beforeEvent);

        // Subsequent calls do not add more subscribers
        $subscriber->before($beforeEvent);
    }
}
