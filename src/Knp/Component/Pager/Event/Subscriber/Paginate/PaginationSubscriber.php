<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\PaginationEvent;
use Knp\Component\Pager\Event\BeforeEvent;
use Knp\Component\Pager\Pagination\SlidingPagination;
use ReflectionClass;

class PaginationSubscriber implements EventSubscriberInterface
{
    public function pagination(PaginationEvent $event)
    {
        $event->setPagination(new SlidingPagination);
        $event->stopPropagation();
    }

    public function before(BeforeEvent $event)
    {
        $disp = $event->getEventDispatcher();
        // hook all standard subscribers
        $disp->addSubscriber(new ArraySubscriber);
        $disp->addSubscriber(new Doctrine\ORM\QuerySubscriber);
        $disp->addSubscriber(new Doctrine\ODM\MongoDB\QuerySubscriber);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'before' => array('before', 0),
            'pagination' => array('pagination', 0)
        );
    }
}