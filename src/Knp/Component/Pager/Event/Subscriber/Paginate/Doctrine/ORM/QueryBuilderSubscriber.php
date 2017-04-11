<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof QueryBuilder) {
            // change target into query
            $queryBuilder = $event->target;

            // Remove "order by" part for the count query for performance purpose
            $countQueryBuilder = clone $queryBuilder;
            $countQueryBuilder->resetDQLPart('orderBy');

            $event->target = $queryBuilder->getQuery();
            $event->countTarget = $countQueryBuilder->getQuery();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 10/*make sure to transform before any further modifications*/)
        );
    }
}