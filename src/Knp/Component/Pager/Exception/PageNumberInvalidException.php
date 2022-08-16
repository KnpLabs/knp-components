<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;

final class PageNumberInvalidException extends OutOfRangeException
{
    private ?int $page = null;

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public static function create(int $page): self
    {
        $exception = new self(
            sprintf('Invalid page number. Page: %d: $page must be positive non-zero integer', $page)
        );

        $exception->setPage($page);

        return $exception;
    }
}