<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
class ItemsEvent extends Event
{
    private $target;
    private $offset;
    private $limit;
    private $items;
    private $options;

    public function __construct($target, $offset, $limit, array $options)
    {
        $this->target = $target;
        $this->offset = $offset;
        $this->limit = $limit;
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
