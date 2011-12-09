<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\CountEvent;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderSubscriber implements EventSubscriberInterface
{
    /**
     * @param CountEvent $event
     */
    public function count(CountEvent $event)
    {
        $this->handle($event);
    }

    public function items(ItemsEvent $event)
    {
        $this->handle($event);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'items' => array('items', 10/*make sure to transform before any further modifications*/),
            'count' => array('count', 10)
        );
    }

    private function handle($event)
    {
        if ($event->target instanceof QueryBuilder) {
            // change target into query
            $event->target = $event->target->getQuery();
        }
    }
}