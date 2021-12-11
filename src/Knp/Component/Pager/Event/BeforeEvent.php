<?php

namespace Knp\Component\Pager\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Specific Event class for paginator
 */
final class BeforeEvent extends Event
{
    private EventDispatcherInterface $eventDispatcher;

    private ?Request $request;

    public function __construct(EventDispatcherInterface $eventDispatcher, ?Request $request)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->request = $request;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getRequest(): Request
    {
        return $this->request ?? Request::createFromGlobals();
    }
}
