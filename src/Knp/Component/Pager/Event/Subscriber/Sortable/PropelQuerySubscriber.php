<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Exception\InvalidValueException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event): void
    {
        $argumentAccess = $event->getArgumentAccess();
        
        // Check if the result has already been sorted by another sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        $query = $event->target;
        if ($query instanceof \ModelCriteria) {
            $event->setCustomPaginationParameter('sorted', true);
            $sortField = $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME];
            $sortDir = $event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME];
            if (null !== $sortField && $argumentAccess->has($sortField)) {
                $part = $argumentAccess->get($sortField);
                $direction = (null !== $sortDir && $argumentAccess->has($sortDir) && strtolower($argumentAccess->get($sortDir)) === 'asc')
                                ? 'asc' : 'desc';

                if (isset($event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST]) && !in_array($argumentAccess->get($sortField), $event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST])) {
                    throw new InvalidValueException("Cannot sort by: [{$argumentAccess->get($sortField)}] this field is not in allow list.");
                }

                $query->orderBy($part, $direction);
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
