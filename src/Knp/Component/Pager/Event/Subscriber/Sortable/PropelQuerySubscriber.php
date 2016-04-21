<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $query = $event->target;
        if ($query instanceof \ModelCriteria) {
            if (isset($_GET[$event->options['sortFieldParameterName']])) {
                $part = $_GET[$event->options['sortFieldParameterName']];
                $directionParam = $event->options['sortDirectionParameterName'];

                $direction = (isset($_GET[$directionParam]) && strtolower($_GET[$directionParam]) === 'asc')
                                ? 'asc' : 'desc';

                if (isset($event->options['sortFieldWhitelist'])) {
                    if (!in_array($_GET[$event->options['sortFieldParameterName']], $event->options['sortFieldWhitelist'])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options['sortFieldParameterName']]}] this field is not in whitelist");
                    }
                }

                $query->orderBy($part, $direction);
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
