<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Elastica\Query;
use Elastica\SearchableInterface;
use Knp\Component\Pager\PaginatorInterface;

class ElasticaQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event): void
    {
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if (is_array($event->target) && 2 === count($event->target) && reset($event->target) instanceof SearchableInterface && end($event->target) instanceof Query) {
            [$searchable, $query] = $event->target;
            $event->setCustomPaginationParameter('sorted', true);

            if (isset($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]])) {
                $field = $_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]];
                $dir   = isset($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) && strtolower($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) === 'asc' ? 'asc' : 'desc';

                if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST]) && !in_array($field, $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
                    throw new \UnexpectedValueException(sprintf('Cannot sort by: [%s] this field is not in whitelist',$field));
                }

                $query->setSort([
                    $field => ['order' => $dir],
                ]);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => ['items', 1]
        ];
    }
}
