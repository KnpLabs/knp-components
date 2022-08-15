<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;
use Throwable;

final class PageLimitInvalidException extends OutOfRangeException
{
    private int $limit;

    public function __construct(?string $message, int $limit, ?Throwable $previousException = null)
    {
        parent::__construct($message, 0, $previousException);

        $this->limit = $limit;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}