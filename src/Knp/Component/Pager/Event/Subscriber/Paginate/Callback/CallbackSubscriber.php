<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Callback;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class CallbackSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof Target) {
            $event->count = $event->target->count();
            $event->items = $event->target->items(
                $event->getOffset(),
                $event->getLimit()
            );
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}
