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
    private $alias;

    public function __construct($target, $alias)
    {
        $this->target = $target;
        $this->alias = $alias;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getAlias()
    {
        return $this->alias;
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
