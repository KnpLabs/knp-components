<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration;

use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        $query = $event->target;

        if (!$query instanceof \ModelCriteria) {
            return;
        }

        $parametersResolver = $event->getParametersResolver();

        $filterField = $parametersResolver->get(
            $event->options[PaginatorInterface::FILTER_FIELD_PARAMETER_NAME],
            $event->options[PaginatorInterface::DEFAULT_FILTER_FIELDS] ?? null
        );

        if ($filterField === null) {
            return;
        }

        $filterValue = $parametersResolver->get($event->options[PaginatorInterface::FILTER_VALUE_PARAMETER_NAME], null);
        if ($filterValue === null) {
            return;
        }

        $whiteList = $event->options[PaginatorInterface::FILTER_FIELD_WHITELIST] ?? [];
        if (count($whiteList) !== 0 && !in_array($filterField, $whiteList, true)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot sort by: [%s] this field is not in whitelist', $filterField)
            );
        }

        $columns = explode(',', $filterField);

        $criteria = \Criteria::EQUAL;
        if (false !== strpos($filterValue, '*')) {
            $filterValue = str_replace('*', '%', $filterValue);
            $criteria = \Criteria::LIKE;
        }

        foreach ($columns as $column) {
            if (false !== strpos($column, '.')) {
                $query->where($column.$criteria.'?', $filterValue);

                continue;
            }

            $query->{'filterBy'.$column}($filterValue, $criteria);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 0],
        ];
    }
}
