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
        $query = $event->getTarget();
        if ($query instanceof Query) {
            $countQuery = QueryHelper::cloneQuery($query);
            QueryHelper::addCustomTreeWalker(
                $countQuery,
                'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\CountWalker'
            );
            $countQuery
                ->setHint(CountWalker::HINT_PAGINATOR_COUNT_DISTINCT, $event->isDistinct())
                ->setFirstResult(null)
                ->setMaxResults(null)
            ;

            $countResult = $countQuery->getResult(Query::HYDRATE_ARRAY);
            $event->setCount(count($countResult) > 1 ? count($countResult) : current(current($countResult)));
            $event->stopPropagation();
        }
    }

    public function items(ItemsEvent $event)
    {
        $query = $event->getTarget();
        if ($query instanceof Query) {
            $result = null;
            if ($event->isDistinct()) {
                $limitSubQuery = QueryHelper::cloneQuery($query);
                QueryHelper::addCustomTreeWalker(
                    $limitSubQuery,
                    'Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\LimitSubqueryWalker'
                );

                $limitSubQuery
                    ->setFirstResult($event->getOffset())
                    ->setMaxResults($event->getLimit())
                ;

                $ids = array_map('current', $limitSubQuery->getScalarResult());
                // create where-in query
                $whereInQuery = QueryHelper::cloneQuery($query);
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
                $query
                    ->setFirstResult($event->getOffset())
                    ->setMaxResults($event->getLimit())
                ;
                $result = $query->execute();
            }
            $event->setItems($result);
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'items' => array('items', 0),
            'count' => array('count', 0)
        );
    }
}