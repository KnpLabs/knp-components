<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine;

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
    public function items(ItemsEvent $event): void
    {
        if ($event->target instanceof QueryBuilder) {
            /** @var QueryBuilder $target */
            $target = $event->target;
        
            // count results
            $qb = clone $target;
            
            //reset count orderBy since it can break query and slow it down
            $qb
                ->resetQueryPart('orderBy')
            ;
            
            // get the query
            $sql = $qb->getSQL();
            
            $qb
                ->resetQueryParts()
                ->select('count(*) as cnt')
                ->from('(' . $sql . ')', 'dbal_count_tbl')
            ;

            $compat = $qb->execute();
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
                    ->execute()
                    ->fetchAll()
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
