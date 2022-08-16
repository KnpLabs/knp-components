<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\Exception\PageLimitInvalidException;
use Knp\Component\Pager\Exception\PageNumberInvalidException;
use Test\Tool\BaseTestCase;

final class PaginatorTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldThrowExceptionIfPageIsInvalid(): void
    {
        $this->expectException(PageNumberInvalidException::class);

        $paginator = $this->getPaginatorInstance();
        $paginator->paginate(['a', 'b'], 0, -1);
    }

    /**
     * @test
     */
    public function shouldGetPageFromException(): void
    {
        try {
            $paginator = $this->getPaginatorInstance();
            $paginator->paginate(['a', 'b'], 0, -1);
        } catch (PageNumberInvalidException $e) {
            $this->assertEquals(0, $e->getPage());
        }
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfLimitIsInvalid(): void
    {
        $this->expectException(PageLimitInvalidException::class);

        $paginator = $this->getPaginatorInstance();
        $paginator->paginate(['a', 'b'], 10, -1);
    }

    /**
     * @test
     */
    public function shouldGetLimitFromException(): void
    {
        try {
            $paginator = $this->getPaginatorInstance();
            $paginator->paginate(['a', 'b'], 10, -1);
        } catch (PageLimitInvalidException $e) {
            $this->assertEquals(-1, $e->getLimit());
        }
    }
}
