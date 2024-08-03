<?php

namespace Test\Pager\Subscriber;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\BeforeEvent;
use Knp\Component\Pager\Event\Subscriber\Filtration\FiltrationSubscriber;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\Tool\BaseTestCase;

final class FiltrationSubscriberTest extends BaseTestCase
{
    #[Test]
    public function shouldRegisterExpectedSubscribersOnlyOnce(): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $dispatcher->expects($this->exactly(2))->method('addSubscriber');

        $subscriber = new FiltrationSubscriber;

        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $beforeEvent = new BeforeEvent($dispatcher, $accessor);
        $subscriber->before($beforeEvent);

        // Subsequent calls do not add more subscribers
        $subscriber->before($beforeEvent);
    }
}
