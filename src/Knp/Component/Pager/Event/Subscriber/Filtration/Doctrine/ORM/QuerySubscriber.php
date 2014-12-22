<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query\WhereWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof Query) {
            if (!isset($_GET[$event->options['filterValueParameterName']]) || (empty($_GET[$event->options['filterValueParameterName']]) && $_GET[$event->options['filterValueParameterName']] !== "0")) {
                return;
            }
            if (!empty($_GET[$event->options['filterFieldParameterName']])) {
                $columns = $_GET[$event->options['filterFieldParameterName']];
            } elseif (!empty($event->options['defaultFilterFields'])) {
                $columns = $event->options['defaultFilterFields'];
            } else {
                return;
            }
            $value = $_GET[$event->options['filterValueParameterName']];
            if (false !== strpos($value, '*')) {
                $value = str_replace('*', '%', $value);
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
            $event->target
                    ->setHint(WhereWalker::HINT_PAGINATOR_FILTER_VALUE, $value)
                    ->setHint(WhereWalker::HINT_PAGINATOR_FILTER_COLUMNS, $columns);
            QueryHelper::addCustomTreeWalker($event->target, 'Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query\WhereWalker');
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
                'knp_pager.items' => array('items', 0),
        );
    }
}
