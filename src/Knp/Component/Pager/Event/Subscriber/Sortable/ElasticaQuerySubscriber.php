<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Elastica\Query;
use Elastica\SearchableInterface;

class ElasticaQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->target) && 2 === count($event->target) && reset($event->target) instanceof SearchableInterface && end($event->target) instanceof Query) {
            list($searchable, $query) = $event->target;

            if (isset($_GET[$event->options['sortFieldParameterName']])) {
                $field = $_GET[$event->options['sortFieldParameterName']];
                $dir   = isset($_GET[$event->options['sortDirectionParameterName']]) && strtolower($_GET[$event->options['sortDirectionParameterName']]) === 'asc' ? 'asc' : 'desc';

                if (isset($event->options['sortFieldWhitelist']) && !in_array($field, $event->options['sortFieldWhitelist'])) {
                    throw new \UnexpectedValueException(sprintf('Cannot sort by: [%s] this field is not in whitelist',$field));
                }

                $query->setSort(array(
                    $field => array('order' => $dir),
                ));
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
