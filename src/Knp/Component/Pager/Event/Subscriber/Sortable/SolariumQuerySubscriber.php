<?php
namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class SolariumQuerySubscriber implements EventSubscriberInterface
{

    /**
     * @param ItemsEvent $event
     */
    public function items(ItemsEvent $event)
    {
        // Ensure we are only applying this to solarium queries
        if (is_array($event->target) && 2 === count($event->target) && isset($event->target[0], $event->target[1]) &&
            $event->target[0] instanceof \Solarium_Client && $event->target[1] instanceof \Solarium_Query_Select) {

            if (isset($_GET[$event->options['sortFieldParameterName']]) && !empty($_GET[$event->options['sortFieldParameterName']]) ) {

                list($field, $dir) = explode('.', $_GET[$event->options['sortFieldParameterName']]);

                // Esnure field is set, otherwise don't attempt to sort
                if (isset($field)) {

                    $dir = (isset($dir)) ? $dir : 'asc';

                    list($client, $query) = $event->target;

                    $results = array();
                    $event->count = $client->select($query)->getNumFound();

                    if ($event->count) {
                        $query->addSort($field, $dir);
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
    }

    /**
     * @static
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 1)
        );
    }

}
