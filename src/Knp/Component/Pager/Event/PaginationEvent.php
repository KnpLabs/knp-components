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
     * @var array<string, mixed>
     */
    public array $options;

    /**
     * @var PaginationInterface<int, mixed>
     */
    private PaginationInterface $pagination;

    /**
     * @param PaginationInterface<int, mixed> $pagination
     */
    public function setPagination(PaginationInterface $pagination): void
    {
        $this->pagination = $pagination;
    }

    /**
     * @return PaginationInterface<int, mixed>
     */
    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }
}
