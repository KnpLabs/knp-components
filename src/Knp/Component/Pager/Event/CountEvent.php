<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
class CountEvent extends Event
{
    const NAME = 'count';

    private $distinct;
    private $target;
    private $count;
    private $alias;

    public function __construct($target, $distinct, $alias)
    {
        $this->target = $target;
        $this->distinct = (bool)$distinct;
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

    /**
     * @todo: maybe a closure to lazy load
     *
     * @param unknown_type $count
     */
    public function setCount($count)
    {
        $this->count = intval($count);
    }

    public function getCount()
    {
        return $this->count;
    }
}
