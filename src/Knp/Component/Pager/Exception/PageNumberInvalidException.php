<?php

namespace Knp\Component\Pager\Exception;

use OutOfRangeException;
use Throwable;

final class PageNumberInvalidException extends OutOfRangeException
{
    private int $page;

    public function __construct(?string $message, int $page, ?Throwable $previousException = null)
    {
        parent::__construct($message, 0, $previousException);

        $this->page = $page;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}