<?php

namespace Knp\Component\Pager\Event;

use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Specific Event class for paginator
 */
final class PaginationEvent extends Event
{
    /**
     * A target being paginated
     */
    public mixed $target = null;

    /**
     * List of options
     */
    public array $options;

    private PaginationInterface $pagination;

    public function setPagination(PaginationInterface $pagination): void
    {
        $this->pagination = $pagination;
    }

    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }
}
