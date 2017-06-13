<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Specific Event class for paginator
 */
class BeforeEvent extends Event
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
}
