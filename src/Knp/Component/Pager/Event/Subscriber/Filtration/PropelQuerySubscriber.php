<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $query = $event->target;
        if ($query instanceof \ModelCriteria) {
            if (empty($_GET[$event->options['filterValueParameterName']])) {
                return;
            }
            if (!empty($_GET[$event->options['filterFieldParameterName']])) {
                $columns = $_GET[$event->options['filterFieldParameterName']];
            } elseif (!empty($event->options['defaultFilterFields'])) {
                $columns = $event->options['defaultFilterFields'];
            } else {
                return;
            }
            if (is_string($columns) && false !== strpos($columns, ',')) {
                $columns = explode(',', $columns);
            }
            $columns = (array) $columns;
            if (isset($event->options['filterFieldWhitelist'])) {
                foreach ($columns as $column) {
                    if (!in_array($column, $event->options['filterFieldWhitelist'])) {
                        throw new \UnexpectedValueException("Cannot filter by: [{$column}] this field is not in whitelist");
                    }
                }
            }
            $value = $_GET[$event->options['filterValueParameterName']];
            $criteria = \Criteria::EQUAL;
            if (false !== strpos($value, '*')) {
                $value = str_replace('*', '%', $value);
                $criteria = \Criteria::LIKE;
            }
            foreach ($columns as $column) {
                if (false !== strpos($column, '.')) {
                    $query->where($column.$criteria.'?', $value);
                } else {
                    $query->{'filterBy'.$column}($value, $criteria);
                }
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0),
        );
    }
}
