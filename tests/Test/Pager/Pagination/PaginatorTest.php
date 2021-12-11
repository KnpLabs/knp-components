<?php

namespace Test\Pager\Pagination;

use Test\Tool\BaseTestCase;

final class PaginatorTest extends BaseTestCase
{
    /**
     * @test
     */
    public function shouldThrowExceptionOnInvalidPageAndLimitArgs(): void
    {
        $exceptionThrown = false;
        try {
            $paginator = $this->getPaginatorInstance();
            $paginator->paginate(['a', 'b'], -1, 0);
        } catch (\LogicException $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown);
    }
}
