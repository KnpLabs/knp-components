<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query\Query;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Exception\InvalidValueException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event): void
    {
        $argumentAccess = $event->getArgumentAccess();

        // Check if the result has already been sorted by another sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if ($event->target instanceof Query) {
            $event->setCustomPaginationParameter('sorted', true);
            $sortField = $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME];
            $sortDir = $event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME];
            if (null !== $sortField && $argumentAccess->has($sortField)) {
                $field = $argumentAccess->get($sortField);
                $dir = null !== $sortDir && strtolower($argumentAccess->get($sortDir)) === 'asc' ? 1 : -1;

                if (isset($event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST]) && (!in_array($field, $event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST]))) {
                    throw new InvalidValueException("Cannot sort by: [$field] this field is not in allow list.");
                }
                static $reflectionProperty;
                if (is_null($reflectionProperty)) {
                    $reflectionClass = new \ReflectionClass(Query::class);
                    $reflectionProperty = $reflectionClass->getProperty('query');
                    $reflectionProperty->setAccessible(true);
                }
                $queryOptions = $reflectionProperty->getValue($event->target);

                // handle multi sort
                $sortFields = explode('+', $field);
                $sortOption = [];
                foreach ($sortFields as $sortField) {
                    $sortOption[$sortField] = $dir;
                }

                $queryOptions['sort'] = $sortOption;
                $reflectionProperty->setValue($event->target, $queryOptions);
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
