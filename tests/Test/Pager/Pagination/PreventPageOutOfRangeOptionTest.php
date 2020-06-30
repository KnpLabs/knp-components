<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\Exception\PageNumberOutOfRangeException;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Test\Tool\BaseTestCase;

final class PreventPageOutOfRangeOptionTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldBeAbleToHandleOutOfRangePageNumberAsArgument(): void
    {
        $p = new Paginator;
        $items = \range(1, 23);
        // "fix" option
        $view = $p->paginate($items, 10, 10, [PaginatorInterface::PAGE_OUT_OF_RANGE => PaginatorInterface::PAGE_OUT_OF_RANGE_FIX]);
        $pagination = $view->getPaginationData();

        $this->assertEquals(3, $pagination['last']);
        $this->assertEquals(3, $pagination['current']);
        $this->assertEquals(2, $pagination['previous']);
        $this->assertEquals(3, $pagination['currentItemCount']);
        $this->assertEquals(21, $pagination['firstItemNumber']);
        $this->assertEquals(23, $pagination['lastItemNumber']);

        // "throwException" option
        $this->expectException(PageNumberOutOfRangeException::class);
        $p->paginate($items, 10, 10, [PaginatorInterface::PAGE_OUT_OF_RANGE => PaginatorInterface::PAGE_OUT_OF_RANGE_THROW_EXCEPTION]);
    }

    /**
     * @test
     */
    public function shouldBeAbleToHandleOutOfRangePageNumberAsDefaultOption(): void
    {
        $p = new Paginator;
        $items = \range(1, 23);
        // "fix" option
        $p->setDefaultPaginatorOptions([
            PaginatorInterface::PAGE_OUT_OF_RANGE => PaginatorInterface::PAGE_OUT_OF_RANGE_FIX,
        ]);
        $view = $p->paginate($items, 10, 10);
        $pagination = $view->getPaginationData();

        $this->assertEquals(3, $pagination['last']);
        $this->assertEquals(3, $pagination['current']);
        $this->assertEquals(2, $pagination['previous']);
        $this->assertEquals(3, $pagination['currentItemCount']);
        $this->assertEquals(21, $pagination['firstItemNumber']);
        $this->assertEquals(23, $pagination['lastItemNumber']);

        // "throwException" option
        $p->setDefaultPaginatorOptions([
            PaginatorInterface::PAGE_OUT_OF_RANGE => PaginatorInterface::PAGE_OUT_OF_RANGE_THROW_EXCEPTION,
        ]);
        $this->expectException(PageNumberOutOfRangeException::class);
        $p->paginate($items, 10, 10);
    }
}
