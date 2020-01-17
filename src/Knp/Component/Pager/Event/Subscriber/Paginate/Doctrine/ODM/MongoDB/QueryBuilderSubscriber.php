<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query\Builder;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QueryBuilderSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event): void
    {
        if ($event->target instanceof Builder) {
            // change target into query
            $event->target = $event->target->getQuery();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 10/*make sure to transform before any further modifications*/]
        ];
    }
}
