<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\PaginatorInterface;
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
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if (!is_array($event->target) || count($event->target) !== 2) {
            return;
        }

        $event->setCustomPaginationParameter('sorted', true);

        [$client, $query] = array_values($event->target);

        if (!$client instanceof \Solarium\Client || !$query instanceof \Solarium\QueryType\Select\Query\Query) {
            return;
        }

        $parametersResolver = $event->getParametersResolver();
        $field = $parametersResolver->get(
            $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME],
            $event->options['defaultSortFieldName'] ?? null
        );

        if ($field === null) {
            return;
        }

        $direction = $parametersResolver->get(
            $event->options['sortDirectionParameterName'],
            $event->options['defaultSortDirection'] ?? 'asc'
        );

        $whiteList = $event->options['sortFieldWhitelist'] ?? [];
        if (count($whiteList) !== 0 && !in_array($field, $whiteList, true)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot sort by: [%s] this field is not in whitelist', $field)
            );
        }

        $query->addSort($field, $direction);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // trigger before the pagination subscriber
            'knp_pager.items' => ['items', 1],
        ];
    }
}
