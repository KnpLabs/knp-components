<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\Query\OrderByWalker;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Doctrine\ORM\Query;
use Knp\Component\Pager\PaginatorInterface;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (!$event->target instanceof Query) {
            return;
        }

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

        $fields = explode('+', $field);
        $sortedFields = [];
        $aliases = [];
        foreach ($fields as $field) {
            $parts = explode('.', $field, 2);

            // We have to prepend the field. Otherwise OrderByWalker will add
            // the order-by items in the wrong order
            array_unshift($sortedFields, end($parts));
            array_unshift($aliases, 2 <= count($parts) ? reset($parts) : false);
        }

        $event->target
            ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_DIRECTION, $direction)
            ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_FIELD, $sortedFields)
            ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_ALIAS, $aliases)
        ;

        QueryHelper::addCustomTreeWalker($event->target, OrderByWalker::class);
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            'knp_pager.items' => ['items', 1]
        );
    }
}
