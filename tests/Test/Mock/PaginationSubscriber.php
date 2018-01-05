<?php

namespace Test\Mock;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\PaginationEvent;
use Knp\Component\Pager\Pagination\SlidingPagination;

class PaginationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.pagination' => ['pagination', 0]
        ];
    }

    public function pagination(PaginationEvent $e): void
    {
        $e->setPagination(new SlidingPagination);
        $e->stopPropagation();
    }
}