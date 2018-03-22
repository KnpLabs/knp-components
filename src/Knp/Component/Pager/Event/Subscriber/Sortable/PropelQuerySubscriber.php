<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if (!$event->target instanceof \ModelCriteria) {
            return;
        }

        $event->setCustomPaginationParameter('sorted', true);

        $query = $event->target;

        $parametersResolver = $event->getParametersResolver();
        $field = $parametersResolver->get(
            $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME],
            $event->options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME] ?? null
        );

        if ($field === null) {
            return;
        }

        $direction = $parametersResolver->get(
            $event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME],
            $event->options[PaginatorInterface::DEFAULT_SORT_DIRECTION] ?? 'asc'
        );

        $whiteList = $event->options['sortFieldWhitelist'] ?? [];
        if (count($whiteList) !== 0 && !in_array($field, $whiteList, true)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot sort by: [%s] this field is not in whitelist', $field)
            );
        }

        $query->orderBy($field, $direction);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1]
        ];
    }
}
