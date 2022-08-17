<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;

final class PageNumberInvalidException extends OutOfRangeException
{
    public static function create(int $page): self
    {
        return new self(
            sprintf('Invalid page number. Page: %d: $page must be positive non-zero integer', $page)
        );
    }
}
