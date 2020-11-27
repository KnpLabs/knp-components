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
     *
     * @var mixed
     */
    public $target;

    /**
     * List of options
     *
     * @var array
     */
    public $options;

    private $pagination;

    public function setPagination(PaginationInterface $pagination): void
    {
        $this->pagination = $pagination;
    }

    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }
}
