<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuerySubscriber implements EventSubscriberInterface
{
    public const HINT_COUNT = 'knp_paginator.count';
    public const HINT_FETCH_JOIN_COLLECTION = 'knp_paginator.fetch_join_collection';

    public function items(ItemsEvent $event): void
    {
        if (!$event->target instanceof Query) {
            return;
        }
        $event->stopPropagation();

        $useOutputWalkers = $event->options['wrap-queries'] ?? false;

        $event->target
            ->setFirstResult($event->getOffset())
            ->setMaxResults($event->getLimit())
            ->setHint(CountWalker::HINT_DISTINCT, $event->options['distinct'])
        ;

        $fetchJoinCollection = true;
        if ($event->target->hasHint(self::HINT_FETCH_JOIN_COLLECTION)) {
            $fetchJoinCollection = $event->target->getHint(self::HINT_FETCH_JOIN_COLLECTION);
        } else if (isset($event->options['distinct'])) {
            $fetchJoinCollection = $event->options['distinct'];
        }

        $paginator = new Paginator($event->target, $fetchJoinCollection);
        $paginator->setUseOutputWalkers($useOutputWalkers);
        if (($count = $event->target->getHint(self::HINT_COUNT)) !== false) {
            $event->count = (int) $count;
        } else {
            $event->count = count($paginator);
        }
        $event->items = iterator_to_array($paginator);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 0],
        ];
    }
}
