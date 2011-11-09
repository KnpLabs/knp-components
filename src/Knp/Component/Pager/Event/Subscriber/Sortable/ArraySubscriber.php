<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class ArraySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->getTarget())) {
            if (isset($_GET[$event->getAlias().'sort'])) {
                $data = $event->getTarget();
                $field = $_GET[$event->getAlias().'sort'];
                $dir = strtolower($_GET[$event->getAlias().'direction']) == 'asc' ? 1 : -1;

                $data = usort($event->getTarget(), function($a, $b) use ($dir) {
                    if ($a != $b) {
                        if ($dir === 'asc') {
                            return $a < $b ? 1 : -1;
                        } else {
                            return $a > $b ? 1 : -1;
                        }
                    }
                    return 0;
                });
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'items' => array('items', 1)
        );
    }
}