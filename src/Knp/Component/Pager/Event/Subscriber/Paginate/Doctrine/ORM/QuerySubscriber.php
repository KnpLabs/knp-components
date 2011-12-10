<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\CountEvent;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\CountWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\WhereInWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\LimitSubqueryWalker;
use Doctrine\ORM\Query;

class QuerySubscriber implements EventSubscriberInterface
{
    /**
     * @param CountEvent $event
     */
    public function count(CountEvent $event)
    {
        if ($event->target instanceof Query) {
            $countQuery = QueryHelper::cloneQuery($event->target);
            QueryHelper::addCustomTreeWalker(
                $countQuery,
                'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\CountWalker'
            );
            $countQuery
                ->setHint(CountWalker::HINT_PAGINATOR_COUNT_DISTINCT, $event->options['distinct'])
                ->setFirstResult(null)
                ->setMaxResults(null)
            ;

            $countResult = $countQuery->getResult(Query::HYDRATE_ARRAY);
            $event->count = count($countResult) > 1 ? count($countResult) : current(current($countResult));
            $event->stopPropagation();
        }
    }

    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof Query) {
            $result = null;
            if ($event->options['distinct']) {
                $limitSubQuery = QueryHelper::cloneQuery($event->target);
                $limitSubQuery
                    ->setFirstResult($event->getOffset())
                    ->setMaxResults($event->getLimit())
                    ->useQueryCache(false)
                ;
                QueryHelper::addCustomTreeWalker(
                    $limitSubQuery,
                    'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\LimitSubqueryWalker'
                );

                $ids = array_map('current', $limitSubQuery->getScalarResult());
                // create where-in query
                $whereInQuery = QueryHelper::cloneQuery($event->target);
                QueryHelper::addCustomTreeWalker(
                    $whereInQuery,
                    'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\WhereInWalker'
                );
                $whereInQuery
                    ->setHint(WhereInWalker::HINT_PAGINATOR_ID_COUNT, count($ids))
                    ->setFirstResult(null)
                    ->setMaxResults(null)
                ;

                $type = $limitSubQuery->getHint(LimitSubqueryWalker::IDENTIFIER_TYPE);
                foreach ($ids as $i => $id) {
                    $whereInQuery->setParameter(
                        WhereInWalker::PAGINATOR_ID_ALIAS . '_' . ++$i,
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
            $event->items = $result;
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0),
            'knp_pager.count' => array('count', 0)
        );
    }
}
