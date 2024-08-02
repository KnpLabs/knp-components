<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;
use Throwable;

class PageNumberOutOfRangeException extends OutOfRangeException
{
    public function __construct(?string $message, private readonly int $maxPageNumber, ?Throwable $previousException = null)
    {
        parent::__construct($message, 0, $previousException);
    }

    public function getMaxPageNumber(): int
    {
        return $this->maxPageNumber;
    }
}
