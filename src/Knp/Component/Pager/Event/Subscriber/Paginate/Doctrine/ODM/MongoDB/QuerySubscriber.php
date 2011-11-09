<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ODM\MongoDB;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\CountEvent;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ODM\MongoDB\Query\Query;

class QuerySubscriber implements EventSubscriberInterface
{
    /**
     * @param CountEvent $event
     */
    public function count(CountEvent $event)
    {
        $query = $event->getTarget();
        if ($query instanceof Query) {
            $event->setCount($query->count());
            $event->stopPropagation();
        }
    }

    public function items(ItemsEvent $event)
    {
        $query = $event->getTarget();
        if ($query instanceof Query) {
            $type = $query->getType();
            if ($type !== Query::TYPE_FIND) {
                throw new \UnexpectedValueException('ODM query must be a FIND type query');
            }
            $reflClass = new \ReflectionClass('Doctrine\MongoDB\Query\Query');
            $reflProp = $reflClass->getProperty('query');
            $reflProp->setAccessible(true);
            $queryOptions = $reflProp->getValue($query);

            $queryOptions['limit'] = $event->getLimit();
            $queryOptions['skip'] = $event->getOffset();

            $resultQuery = clone $query;
            $reflProp->setValue($resultQuery, $queryOptions);
            $cursor = $resultQuery->execute();

            $event->setItems($cursor->toArray());
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