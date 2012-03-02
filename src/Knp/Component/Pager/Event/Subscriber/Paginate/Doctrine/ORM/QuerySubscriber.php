<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\CountWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\WhereInWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\LimitSubqueryWalker;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker as DoctrineCountWalker;
use Doctrine\ORM\Tools\Pagination\WhereInWalker as DoctrineWhereInWalker;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker as DoctrineLimitSubqueryWalker;

class QuerySubscriber implements EventSubscriberInterface
{
    /**
     * Used if user set the count manually
     */
    const HINT_COUNT = 'knp_paginator.count';

    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof Query) {
            // process count
            $useDoctrineWalkers = version_compare(\Doctrine\ORM\Version::VERSION, '2.2.0', '>=');
            if (($count = $event->target->getHint(self::HINT_COUNT)) !== false) {
                $event->count = intval($count);
            } else {
                $countQuery = QueryHelper::cloneQuery($event->target);
                QueryHelper::addCustomTreeWalker($countQuery, $useDoctrineWalkers ?
                    'Doctrine\ORM\Tools\Pagination\CountWalker' :
                    'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\CountWalker'
                );
                $countQuery
                    ->setHint($useDoctrineWalkers ?
                        DoctrineCountWalker::HINT_DISTINCT :
                        CountWalker::HINT_DISTINCT, $event->options['distinct']
                    )
                    ->setFirstResult(null)
                    ->setMaxResults(null)
                ;

                $countResult = $countQuery->getResult(Query::HYDRATE_ARRAY);
                if (count($countResult) > 1) {
                    $countResult = count($countResult);
                } else {
                    $countResult = current($countResult);
                    $countResult = $countResult ? current($countResult) : 0;
                }
                $event->count = intval($countResult);
            }
            // process items
            $result = null;
            if ($event->count) {
                if ($event->options['distinct']) {
                    $limitSubQuery = QueryHelper::cloneQuery($event->target);
                    $limitSubQuery
                        ->setFirstResult($event->getOffset())
                        ->setMaxResults($event->getLimit())
                        ->useQueryCache(false)
                    ;
                    QueryHelper::addCustomTreeWalker($limitSubQuery, $useDoctrineWalkers ?
                        'Doctrine\ORM\Tools\Pagination\LimitSubqueryWalker' :
                        'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\LimitSubqueryWalker'
                    );

                    $ids = array_map('current', $limitSubQuery->getScalarResult());
                    // create where-in query
                    $whereInQuery = QueryHelper::cloneQuery($event->target);
                    QueryHelper::addCustomTreeWalker($whereInQuery, $useDoctrineWalkers ?
                        'Doctrine\ORM\Tools\Pagination\WhereInWalker' :
                        'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\WhereInWalker'
                    );
                    $whereInQuery
                        ->setHint($useDoctrineWalkers ?
                            DoctrineWhereInWalker::HINT_PAGINATOR_ID_COUNT :
                            WhereInWalker::HINT_PAGINATOR_ID_COUNT, count($ids)
                        )
                        ->setFirstResult(null)
                        ->setMaxResults(null)
                    ;

                    $type = $limitSubQuery->getHint($useDoctrineWalkers ?
                        DoctrineLimitSubqueryWalker::IDENTIFIER_TYPE :
                        LimitSubqueryWalker::IDENTIFIER_TYPE
                    );
                    $idAlias = $useDoctrineWalkers ? DoctrineWhereInWalker::PAGINATOR_ID_ALIAS : WhereInWalker::PAGINATOR_ID_ALIAS;
                    foreach ($ids as $i => $id) {
                        $whereInQuery->setParameter(
                            $idAlias . '_' . ++$i,
                            $id,
                            $type->getName()
                        );
                    }
                    $result = $whereInQuery->execute();
                } else {
                    $event->target
                        ->setFirstResult($event->getOffset())
                        ->setMaxResults($event->getLimit())
                    ;
                    $result = $event->target->execute();
                }
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
