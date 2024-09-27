<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Elastica\Query;
use Elastica\SearchableInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Exception\InvalidValueException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ElasticaQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event): void
    {
        $argumentAccess = $event->getArgumentAccess();
        
        // Check if the result has already been sorted by another sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if (is_array($event->target) && 2 === count($event->target) && reset($event->target) instanceof SearchableInterface && end($event->target) instanceof Query) {
            [$searchable, $query] = $event->target;
            $event->setCustomPaginationParameter('sorted', true);
            $sortField = $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME];
            $sortDir = $event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME];
            if (null !== $sortField && $argumentAccess->has($sortField)) {
                $field = $argumentAccess->get($sortField);
                $dir   = null !== $sortDir && $argumentAccess->has($sortDir) && strtolower($argumentAccess->get($sortDir)) === 'asc' ? 'asc' : 'desc';

                if (isset($event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST]) && !in_array($field, $event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST])) {
                    throw new InvalidValueException(sprintf('Cannot sort by: [%s] this field is not in allow list.', $field));
                }

                $query->setSort([
                    $field => ['order' => $dir],
                ]);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1],
        ];
    }
}
