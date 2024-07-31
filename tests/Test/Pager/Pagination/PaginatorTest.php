<?php

namespace Test\Pager\Pagination;

use Knp\Component\Pager\Exception\PageLimitInvalidException;
use Knp\Component\Pager\Exception\PageNumberInvalidException;
use PHPUnit\Framework\Attributes\Test;
use Test\Tool\BaseTestCase;

final class PaginatorTest extends BaseTestCase
{
    #[Test]
    public function shouldThrowExceptionIfPageIsInvalid(): void
    {
        $this->expectException(PageNumberInvalidException::class);

        $paginator = $this->getPaginatorInstance();
        $paginator->paginate(['a', 'b'], 0, 10);
    }

    #[Test]
    public function shouldThrowExceptionIfLimitIsInvalid(): void
    {
        $this->expectException(PageLimitInvalidException::class);

        $paginator = $this->getPaginatorInstance();
        $paginator->paginate(['a', 'b'], 10, -1);
    }
}
