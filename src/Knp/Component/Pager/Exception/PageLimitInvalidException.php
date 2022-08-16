<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;

final class PageLimitInvalidException extends OutOfRangeException
{
    private ?int $limit = null;

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public static function create(int $limit): self
    {
        $exception = new self(
            sprintf('Invalid page limit. Limit: %d: $limit must be positive non-zero integer', $limit)
        );

        $exception->setLimit($limit);

        return $exception;
    }
}