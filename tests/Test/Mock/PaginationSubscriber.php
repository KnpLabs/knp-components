<?php

namespace Test\Mock;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\InitPaginationEvent;
use Knp\Component\Pager\Pagination\SlidingPagination;

class PaginationSubscriber implements EventSubscriberInterface
{
    static function getSubscribedEvents()
    {
        return array(
            'pagination' => array('pagination', 0)
        );
    }

    function pagination(InitPaginationEvent $e)
    {
        $e->setPagination(new SlidingPagination);
        $e->stopPropagation();
    }
}