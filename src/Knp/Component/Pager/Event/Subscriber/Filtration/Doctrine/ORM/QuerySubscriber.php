<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query\WhereWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (!$event->target instanceof Query) {
            return;
        }

        $parametersResolver = $event->getParametersResolver();
        $filterField = $parametersResolver->get(
            $event->options['filterFieldParameterName'],
            $event->options['defaultFilterFields'] ?? null
        );

        if ($filterField === null || '' === $filterField) {
            return;
        }

        $filterValue = $parametersResolver->get($event->options['filterValueParameterName'], null);
        if ($filterValue === null || '' === $filterValue) {
            return;
        }

        $whiteList = $event->options['filterFieldWhitelist'] ?? [];
        if (count($whiteList) !== 0 && !in_array($filterField, $whiteList, true)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot sort by: [%s] this field is not in whitelist', $filterField)
            );
        }

        $filterValue = str_replace('*', '%', $filterValue);

        $columns = explode(',', $filterField);
        $event->target
            ->setHint(WhereWalker::HINT_PAGINATOR_FILTER_VALUE, $filterValue)
            ->setHint(WhereWalker::HINT_PAGINATOR_FILTER_COLUMNS, $columns);

        QueryHelper::addCustomTreeWalker($event->target, WhereWalker::class);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 0],
        ];
    }
}
