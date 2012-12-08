<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Solarium query sorting
 *
 * @author Marek Kalnik <marekk@theodo.fr>
 */
class SolariumQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->target) && 2 === count($event->target) && reset($event->target) instanceof \Solarium_Client && end($event->target) instanceof \Solarium_Query_Select) {
            list($client, $query) = $event->target;

            if (isset($_GET[$event->options['sortFieldParameterName']])) {
                if (isset($event->options['sortFieldWhitelist'])) {
                    if (!in_array($_GET[$event->options['sortFieldParameterName']], $event->options['sortFieldWhitelist'])) {
                        throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options['sortFieldParameterName']]}] this field is not in whitelist");
                    }
                }

                $query->addSort($_GET[$event->options['sortFieldParameterName']], $this->getSortDirection($event));
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            // trigger before the pagination subscriber
            'knp_pager.items' => array('items', 1),
        );
    }

    private function getSortDirection($event)
    {
        return isset($_GET[$event->options['sortDirectionParameterName']]) &&
            strtolower($_GET[$event->options['sortDirectionParameterName']]) === 'asc' ? 'asc' : 'desc';
    }
}
