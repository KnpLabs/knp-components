<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\PaginatorInterface;
use Test\Tool\BaseTestCase;

final class DefaultLimitOptionTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldBeAbleToHandleNullLimit(): void
    {
        $p = $this->getPaginatorInstance();
        $items = \range(1, 23);
        $view = $p->paginate($items, 2, null, []);
        $pagination = $view->getPaginationData();

        $this->assertEquals(PaginatorInterface::DEFAULT_LIMIT_VALUE, $pagination['numItemsPerPage']);
    }

    /**
     * @test
     */
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
