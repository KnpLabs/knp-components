<?php

namespace Knp\Component\Pager\Event;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
final class AfterEvent extends Event
{
    /**
     * @param PaginationInterface<int, mixed> $pagination
     */
    public function __construct(private readonly PaginationInterface $pagination)
    {
    }

    /**
     * @return PaginationInterface<int, mixed>
     */
    public function getPaginationView(): PaginationInterface
    {
        return $this->pagination;
    }
}
