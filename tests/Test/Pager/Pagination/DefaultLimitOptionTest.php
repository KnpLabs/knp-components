<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\Test;
use Test\Tool\BaseTestCase;

final class DefaultLimitOptionTest extends BaseTestCase
{
    #[Test]
    public function shouldBeAbleToHandleNullLimit(): void
    {
        $p = $this->getPaginatorInstance();
        $items = \range(1, 23);
        $view = $p->paginate($items, 2);
        $pagination = $view->getPaginationData();

        $this->assertEquals(PaginatorInterface::DEFAULT_LIMIT_VALUE, $pagination['numItemsPerPage']);
    }

    #[Test]
    public function shouldBeAbleToOverwriteDefaultLimit(): void
    {
        $p = $this->getPaginatorInstance();
        $items = \range(1, 23);
        $p->setDefaultPaginatorOptions([PaginatorInterface::DEFAULT_LIMIT => 8]);
        $view = $p->paginate($items);
        $pagination = $view->getPaginationData();

        $this->assertEquals(8, $pagination['numItemsPerPage']);
    }
}
