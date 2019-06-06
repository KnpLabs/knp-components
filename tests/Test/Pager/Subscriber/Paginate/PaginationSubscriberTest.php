<?php

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\BeforeEvent;

class PaginationSubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldRegisterExpectedSubscribersOnlyOnce(): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $dispatcher->expects($this->exactly(12))->method('addSubscriber');

        $subscriber = new PaginationSubscriber;

        $requestStack = $this->getRequestStack([]);
        $beforeEvent = new BeforeEvent($dispatcher, $requestStack->getCurrentRequest());
        $subscriber->before($beforeEvent);

        // Subsequent calls do not add more subscribers
        $subscriber->before($beforeEvent);
    }
}