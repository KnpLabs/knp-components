<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\CountEvent;
use Knp\Component\Pager\Event\ItemsEvent;

class ArraySubscriber implements EventSubscriberInterface
{
    public function count(CountEvent $event)
    {
        if (is_array($event->getTarget())) {
            $event->setCount(count($event->getTarget()));
            $event->stopPropagation();
        }
    }

    public function items(ItemsEvent $event)
    {
        if (is_array($event->getTarget())) {
            $event->setItems(array_slice(
                $event->getTarget(),
                $event->getOffset(),
                $event->getLimit()
            ));
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'items' => array('items', 0),
            'count' => array('count', 0)
        );
    }
}