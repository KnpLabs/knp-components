<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Propel\Runtime\ActiveQuery\ModelCriteria;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class Propel2QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof ModelCriteria) {
            // process count
            $countQuery = clone $event->target;
            $countQuery
                ->limit(-1)
                ->offset(0)
            ;
            if ($event->options['distinct']) {
                $countQuery->distinct();
            }
            $event->count = intval($countQuery->count());
            // process items
            $result = null;
            if ($event->count) {
                $resultQuery = clone $event->target;
                if ($event->options['distinct']) {
                    $resultQuery->distinct();
                }
                $resultQuery
                    ->offset($event->getOffset())
                    ->limit($event->getLimit())
                ;
                $result = $resultQuery->find()->getData();
            } else {
                $result = array(); // count is 0
            }
            $event->items = $result;
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
