<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
class ItemsEvent extends Event
{
    private $distinct;
    private $target;
    private $offset;
    private $limit;
    private $items;
    private $alias;

    public function __construct($target, $distinct, $offset, $limit, $alias)
    {
        $this->target = $target;
        $this->distinct = (bool)$distinct;
        $this->offset = $offset;
        $this->limit = $limit;
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

    public function isDistinct()
    {
        return $this->distinct;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @todo: maybe a closure to lazy load
     *
     * @param mixed $items
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    public function getItems()
    {
        return $this->items;
    }
}
