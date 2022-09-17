<?php

namespace Knp\Component\Pager\Event;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Specific Event class for paginator
 */
final class BeforeEvent extends Event
{
    private EventDispatcherInterface $eventDispatcher;

    private ArgumentAccessInterface $argumentAccess;

    public function __construct(EventDispatcherInterface $eventDispatcher, ArgumentAccessInterface $argumentAccess)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->argumentAccess = $argumentAccess;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getArgumentAccess(): ArgumentAccessInterface
    {
        return $this->argumentAccess;
    }
}
