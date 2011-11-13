<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ODM\MongoDB\Query\Query;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $query = $event->getTarget();
        if ($query instanceof Query) {
            $alias = $event->getOption('alias');
            if (isset($_GET[$alias.'sort'])) {
                $field = $_GET[$alias.'sort'];
                $dir = strtolower($_GET[$alias.'direction']) == 'asc' ? 1 : -1;

                $meta = $query->getClass();
                if (!$meta->hasField($field)) {
                    throw new \UnexpectedValueException($meta->name.' query cannot be sorted, because does not contain field: '.$field);
                }
                $whiteList = $event->getOption('whitelist');
                if ($whiteList && !in_array($field, $whiteList)) {
                    throw new \UnexpectedValueException("Cannot sort by: [{$field}] this field is not in whitelist");
                }
                $reflClass = new \ReflectionClass('Doctrine\MongoDB\Query\Query');
                $reflProp = $reflClass->getProperty('query');
                $reflProp->setAccessible(true);
                $queryOptions = $reflProp->getValue($query);

                //@todo: seems like does not support multisort ??
                $queryOptions['sort'] = array($field => $dir);
                $reflProp->setValue($query, $queryOptions);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'items' => array('items', 1)
        );
    }
}