<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\CollectionSubscriber;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Tool\BaseTestCase;

final class CollectionTest extends BaseTestCase
{
    #[Test]
    public function shouldPaginateCollection(): void
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CollectionSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $p = new Paginator($dispatcher, $accessor);

        $items = new ArrayCollection(['first', 'second']);
        $view = $p->paginate($items, 1, 10);

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertCount(2, $view->getItems());
        $this->assertEquals(2, $view->getTotalItemCount());
    }

    #[Test]
    public function shouldLoopOverPagination(): void
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CollectionSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $p = new Paginator($dispatcher, $accessor);

        $items = new ArrayCollection(['first', 'second']);
        $view = $p->paginate($items, 1, 10);

        $counter = 0;

        foreach ($view as $item) {
            $this->assertEquals($items[$counter], $item);

            ++$counter;
        }

        $this->assertEquals(2, $counter);
    }

    #[Test]
    public function shouldSlicePaginateAnArray(): void
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CollectionSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $p = new Paginator($dispatcher, $accessor);

        $items = new ArrayCollection(\range('a', 'u'));
        $view = $p->paginate($items, 2, 10);

        $this->assertEquals(2, $view->getCurrentPageNumber());
        $this->assertEquals(10, $view->getItemNumberPerPage());
        $this->assertCount(10, $view->getItems());
        $this->assertEquals(21, $view->getTotalItemCount());
    }

    #[Test]
    public function shouldSupportPaginateStrategySubscriber(): void
    {
        $items = new ArrayCollection(['first', 'second']);
        $p = $this->getPaginatorInstance();
        $view = $p->paginate($items, 1, 10);
        $this->assertInstanceOf(PaginationInterface::class, $view);
    }
}
