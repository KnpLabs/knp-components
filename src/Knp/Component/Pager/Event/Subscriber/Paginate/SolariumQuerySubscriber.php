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
        if (is_array($event->target) && 2 === count($event->target) && reset($event->target) instanceof \Solarium_Client && end($event->target) instanceof \Solarium_Query_Select) {
            list($client, $query) = $event->target;

            $query->setStart($event->getOffset())->setRows($event->getLimit());
            $solrResult = $client->select($query);

            $event->items = $solrResult->getIterator();
            $event->count = $solrResult->getNumFound();
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 0) /* triggers before a standard array subscriber*/
        );
    }
}