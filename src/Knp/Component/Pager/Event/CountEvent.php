<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
class CountEvent extends Event
{
    private $target;
    private $count;
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

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
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
