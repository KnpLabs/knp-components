<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Elastica query pagination.
 *
 */
class ElasticaQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->target) && 2 === count($event->target) && isset($event->target[0], $event->target[1]) &&
            $event->target[0] instanceof \Elastica_Searchable && $event->target[1] instanceof \Elastica_Query) {
                list($searchable, $query) = $event->target;

                $query->setFrom($event->getOffset());
                $query->setLimit($event->getLimit());
                $results = $searchable->search($query);

                $event->count = $results->getTotalHits();
                $event->items = $results->getResults();
                $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0) /* triggers before a standard array subscriber*/
        );
    }
}
