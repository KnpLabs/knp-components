<?php

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\BeforeEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\SortableSubscriber;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\Tool\BaseTestCase;

final class SortableSubscriberTest extends BaseTestCase
{
    #[Test]
    public function shouldRegisterExpectedSubscribersOnlyOnce(): void
    {
        $dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $dispatcher->expects($this->exactly(6))->method('addSubscriber');

        $subscriber = new SortableSubscriber;

        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $beforeEvent = new BeforeEvent($dispatcher, $accessor);
        $subscriber->before($beforeEvent);

        // Subsequent calls do not add more subscribers
        $subscriber->before($beforeEvent);
    }
}
