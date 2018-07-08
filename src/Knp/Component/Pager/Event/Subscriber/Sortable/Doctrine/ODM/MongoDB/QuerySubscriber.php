<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ODM\MongoDB\Query\Query;
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

        $whiteList = $event->options[PaginatorInterface::SORT_FIELD_WHITELIST] ?? [];
        if (count($whiteList) !== 0 && !in_array($field, $whiteList, true)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot sort by: [%s] this field is not in whitelist', $field)
            );
        }

        static $reflectionProperty;
        if ($reflectionProperty === null) {
            $reflectionClass = new \ReflectionClass(\Doctrine\MongoDB\Query\Query::class);
            $reflectionProperty = $reflectionClass->getProperty('query');
            $reflectionProperty->setAccessible(true);
        }

        $queryOptions = $reflectionProperty->getValue($event->target);

        //@todo: seems like does not support multisort ??
        $queryOptions['sort'] = array($field => $direction === 'asc' ? 1 : -1);
        $reflectionProperty->setValue($event->target, $queryOptions);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1]
        ];
    }
}
