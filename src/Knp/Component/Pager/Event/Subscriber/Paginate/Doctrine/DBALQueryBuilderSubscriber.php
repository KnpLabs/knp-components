<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * DBALQueryBuilderSubscriber.php
 *
 * @author Vladimir Chub <v@chub.com.ua>
 */
class DBALQueryBuilderSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function items(ItemsEvent $event): void
    {
        if ($event->target instanceof QueryBuilder) {
            $target = $event->target;

            $qb = $this
                ->connection
                ->createQueryBuilder()
                ->select('COUNT(*)')
                ->from('(' . $target->getSQL() . ')', 'tmp')
                ->setParameters($target->getParameters(), $target->getParameterTypes())
            ;

            $compat = $qb->executeQuery();
            $event->count = method_exists($compat, 'fetchColumn') ? $compat->fetchColumn(0) : $compat->fetchOne();

            // if there is results
            $event->items = [];
            if ($event->count) {
                $qb = clone $target;
                $qb
                    ->setFirstResult($event->getOffset())
                    ->setMaxResults($event->getLimit())
                ;
                
                $event->items = $qb
                    ->executeQuery()
                    ->fetchAllAssociative()
                ;
            }
            
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 10 /*make sure to transform before any further modifications*/],
        ];
    }
}
