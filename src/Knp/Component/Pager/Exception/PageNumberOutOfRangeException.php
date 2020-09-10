<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;
use Throwable;

class PageNumberOutOfRangeException extends OutOfRangeException
{
    /** @var int */
    private $maxPageNumber;

    public function __construct(?string $message, int $maxPageNumber, ?Throwable $previousException = null)
    {
        parent::__construct($message, 0, $previousException);

        $this->maxPageNumber = $maxPageNumber;
    }

    public function getMaxPageNumber(): int
    {
        return $this->maxPageNumber;
    }
}
