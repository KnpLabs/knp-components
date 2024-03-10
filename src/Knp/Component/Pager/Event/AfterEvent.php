<?php

namespace Knp\Component\Pager\Event;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
final class AfterEvent extends Event
{
    /** @var PaginationInterface<int, mixed> */
    private PaginationInterface $pagination;

    /**
     * @param PaginationInterface<int, mixed> $paginationView
     */
    public function __construct(PaginationInterface $paginationView)
    {
        $this->pagination = $paginationView;
    }

    /**
     * @return PaginationInterface<int, mixed>
     */
    public function getPaginationView(): PaginationInterface
    {
        return $this->pagination;
    }
}
