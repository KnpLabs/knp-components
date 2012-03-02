<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Solarium query pagination.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class SolariumQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (is_array($event->target) && 2 === count($event->target)) {
            list($client, $query) = $event->target;
            if ($client instanceof \Solarium_Client && $query instanceof \Solarium_Query_Select) {
                $results = array();
                $event->count = $client->select($query)->getNumFound();
                if ($event->count) {
                    $query
                        ->setStart($event->getOffset())
                        ->setRows($event->getLimit())
                    ;
                    $results = $client->select($query)->getIterator();
                }
                $event->items = $results;
                $event->stopPropagation();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0) /* triggers before a standard array subscriber*/
        );
    }
}
