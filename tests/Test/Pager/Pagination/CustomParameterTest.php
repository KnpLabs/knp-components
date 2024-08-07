<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Mock\CustomParameterSubscriber;
use Test\Mock\PaginationSubscriber as MockPaginationSubscriber;
use Test\Tool\BaseTestCase;

final class CustomParameterTest extends BaseTestCase
{
    #[Test]
    public function shouldGiveCustomParametersToPaginationView(): void
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new CustomParameterSubscriber);
        $dispatcher->addSubscriber(new MockPaginationSubscriber); // pagination view
        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $p = new Paginator($dispatcher, $accessor);

        $items = ['first', 'second'];
        $view = $p->paginate($items, 1, 10);

        $this->assertEquals('val', $view->getCustomParameter('test'));
        $this->assertNull($view->getCustomParameter('nonExisting'));
    }
}
