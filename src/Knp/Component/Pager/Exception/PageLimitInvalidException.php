<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;

final class PageLimitInvalidException extends OutOfRangeException
{
    public static function create(int $limit): self
    {
        return new self(
            sprintf('Invalid page limit. Limit: %d: $limit must be positive non-zero integer', $limit)
        );
    }
}
