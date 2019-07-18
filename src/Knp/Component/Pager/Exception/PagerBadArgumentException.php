<?php

namespace Knp\Component\Pager\Exception;

use Throwable;

/**
 * Class PagerBadArgumentException
 * @package Knp\Component\Pager\Exception
 * @author mboullouz <mboullouz@axescloud.com>
 */
class PagerBadArgumentException extends \Exception {
    /**
     * PagerBadArgumentException constructor.
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
