<?php

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\Tool\BaseTestCase;
use Knp\Component\Pager\Event\Subscriber\Filtration\FiltrationSubscriber;
use Knp\Component\Pager\Event\BeforeEvent;

class FiltrationSubscriberTest extends BaseTestCase
{
    /**
     * @test
     */
    function shouldRegisterExpectedSubscribersOnlyOnce()
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $dispatcher->expects($this->exactly(2))->method('addSubscriber');

        $subscriber = new FiltrationSubscriber;

        $beforeEvent = new BeforeEvent($dispatcher);
        $subscriber->before($beforeEvent);

        // Subsequent calls do not add more subscribers
        $subscriber->before($beforeEvent);
    }
}