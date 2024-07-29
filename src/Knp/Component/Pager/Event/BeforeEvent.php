<?php

namespace Knp\Component\Pager\Event;

use Doctrine\DBAL\Connection;
use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Specific Event class for paginator
 */
final class BeforeEvent extends Event
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ArgumentAccessInterface $argumentAccess,
        private ?Connection $connection = null
    ) {
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getArgumentAccess(): ArgumentAccessInterface
    {
        return $this->argumentAccess;
    }

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }
}
