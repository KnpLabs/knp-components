<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\CountEvent;
use Knp\Component\Pager\Event\ItemsEvent;
use Traversable, Countable;

class ArraySubscriber implements EventSubscriberInterface
{
    public function count(CountEvent $event)
    {
        if (is_array($event->target)) {
            $event->count = count($event->target);
            $event->stopPropagation();
        }
    }

    public function items(ItemsEvent $event)
    {
        if (is_array($event->target)) {
            $event->items = array_slice(
                $event->target,
                $event->getOffset(),
                $event->getLimit()
            );
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0),
            'knp_pager.count' => array('count', 0)
        );
    }
}