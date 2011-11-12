<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Specific Event class for paginator
 */
class PaginationEvent extends Event
{
    private $target;
    private $pagination;
    private $options;

    public function __construct($target, array $options)
    {
        $this->target = $target;
        $this->options = $options;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function setPagination(PaginationInterface $pagination)
    {
        $this->pagination = $pagination;
    }

    public function getPagination()
    {
        return $this->pagination;
    }
}
