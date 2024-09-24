<?php

namespace Test\Pager;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\BeforeEvent;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Test\Tool\BaseTestCase;

final class PaginatorTest extends BaseTestCase
{
    #[Test]
    public function shouldNotBeAbleToPaginateWithoutListeners(): void
    {
        $this->expectException(\RuntimeException::class);

        $paginator = $this->getPaginatorInstance(null, new EventDispatcher());
        $paginator->paginate([]);
    }

    #[Test]
    public function shouldFailToPaginateUnsupportedValue(): void
    {
        $this->expectException(\RuntimeException::class);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());

        $paginator = $this->getPaginatorInstance(null, $dispatcher);
        $paginator->paginate(null, 1, 10);
    }

    #[Test]
    public function shouldPassOptionsToBeforeEventSubscriber(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [
                    'knp_pager.before' => ['before', 1],
                ];
            }
            public function before(BeforeEvent $event): void
            {
                BaseTestCase::assertArrayHasKey('some_option', $event->options);
                BaseTestCase::assertEquals('value', $event->options['some_option']);

                $event->options['some_option'] = 'changed';
                $event->options['extra_option'] = 'added';
            }
        });
        $dispatcher->addSubscriber(new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [
                    'knp_pager.items' => ['items', 1],
                ];
            }
            public function items(ItemsEvent $event): void
            {
                BaseTestCase::assertArrayHasKey('some_option', $event->options);
                BaseTestCase::assertEquals('changed', $event->options['some_option']);
                BaseTestCase::assertArrayHasKey('extra_option', $event->options);
                BaseTestCase::assertEquals('added', $event->options['extra_option']);
            }
        });
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $paginator = $this->getPaginatorInstance(null, $dispatcher);

        $paginator->paginate([], 1, 10, ['some_option' => 'value']);
    }
    #[Test]
    public function shouldPassArgumentAccessToItemsEventSubscriber(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [
                    'knp_pager.items' => ['items', 1],
                ];
            }
            public function items(ItemsEvent $event): void
            {
                BaseTestCase::assertInstanceOf(ArgumentAccessInterface::class, $event->getArgumentAccess());
            }
        });

        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $paginator = new Paginator($dispatcher, $accessor);

        $paginator->paginate([]);
    }
}
