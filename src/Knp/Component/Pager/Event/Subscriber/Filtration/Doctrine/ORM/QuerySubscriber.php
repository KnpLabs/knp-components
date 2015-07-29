<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query\FilterByWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Doctrine\ORM\Query;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $query = $event->target;
        if ($query instanceof Query) {
            if (isset($_GET[$event->options['filterFieldParameterName']])
                && isset($_GET[$event->options['filterValueParameterName']])) {

                $field = explode('.', $_GET[$event->options['filterFieldParameterName']]);
                $value = $_GET[$event->options['filterValueParameterName']];

                if (isset($event->options['filterFieldWhitelist'])) {
                    if (!in_array($_GET[$event->options['filterFieldParameterName']], $event->options['filterFieldWhitelist'])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options['filterFieldParameterName']]}] this field is not in whitelist");
                    }
                }

                $query->setHint(FilterByWalker::HINT_PAGINATOR_FILTER_FIELD, end($field));
                $query->setHint(FilterByWalker::HINT_PAGINATOR_FILTER_VALUE, $value);
                if (count($field) >= 2) {
                    $query->setHint(FilterByWalker::HINT_PAGINATOR_FILTER_ALIAS, reset($field));
                }
                QueryHelper::addCustomTreeWalker($query, 'Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query\FilterByWalker');
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0)
        );
    }
}
