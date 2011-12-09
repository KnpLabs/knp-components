<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ODM\MongoDB\Query\Query;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof Query) {
            $alias = $event->options['alias'];
            if (isset($_GET[$alias.'sort'])) {
                $field = $_GET[$alias.'sort'];
                $dir = strtolower($_GET[$alias.'direction']) == 'asc' ? 1 : -1;

                $meta = $event->target->getClass();
                if (!$meta->hasField($field)) {
                    throw new \UnexpectedValueException($meta->name.' query cannot be sorted, because does not contain field: '.$field);
                }
                if (isset($event->options['whitelist'])) {
                    if (!in_array($field, $event->options['whitelist'])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$field}] this field is not in whitelist");
                    }
                }
                static $reflectionProperty;
                if (is_null($reflectionProperty)) {
                    $reflectionClass = new \ReflectionClass('Doctrine\MongoDB\Query\Query');
                    $reflectionProperty = $reflectionClass->getProperty('query');
                    $reflectionProperty->setAccessible(true);
                }
                $queryOptions = $reflectionProperty->getValue($event->target);

                //@todo: seems like does not support multisort ??
                $queryOptions['sort'] = array($field => $dir);
                $reflectionProperty->setValue($event->target, $queryOptions);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 1)
        );
    }
}