<?php

namespace Test\Pager;

use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
}
