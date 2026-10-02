<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\Helper as QueryHelper;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\Query\OrderByWalker;
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
                if (isset($event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST]) && !in_array($argumentAccess->get($sortField), $event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST])) {
                    throw new InvalidValueException("Cannot sort by: [{$argumentAccess->get($sortField)}] this field is not in allow list.");
                }

                $sortFieldParameterNames = $argumentAccess->get($sortField);
                $fields = [];
                $aliases = [];
                if (!is_string($sortFieldParameterNames)) {
                    throw new InvalidValueException('Cannot sort with array parameter.');
                }

                $sortDirectionParameterValue = null !== $sortDir && $argumentAccess->has($sortDir) ? $argumentAccess->get($sortDir) : null;
                if (null !== $sortDirectionParameterValue && !is_string($sortDirectionParameterValue)) {
                    throw new InvalidValueException('Cannot sort with array parameter.');
                }

                foreach (explode('+', $sortFieldParameterNames) as $sortFieldParameterName) {
                    $parts = explode('.', $sortFieldParameterName, 2);

                    // We have to prepend the field. Otherwise, OrderByWalker will add
                    // the order-by items in the wrong order
                    array_unshift($fields, end($parts));
                    array_unshift($aliases, 2 <= count($parts) ? reset($parts) : false);
                }

                $event->target
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_DIRECTION, $this->resolveDirections($sortDirectionParameterValue, count($fields)))
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_FIELD, $fields)
                    ->setHint(OrderByWalker::HINT_PAGINATOR_SORT_ALIAS, $aliases)
                ;

                QueryHelper::addCustomTreeWalker($event->target, OrderByWalker::class);
            }
        }
    }

    /**
     * Directions are given the same way as the fields, joined by a "+", and are matched to them by
     * position. A single direction therefore applies to every field, and a missing one repeats the
     * last given direction.
     *
     * @return list<string>
     */
    private function resolveDirections(?string $sortDirectionParameterValue, int $fieldCount): array
    {
        $directions = [];
        foreach (explode('+', (string) $sortDirectionParameterValue) as $direction) {
            $directions[] = 'asc' === strtolower($direction) ? 'asc' : 'desc';
        }

        $directions = array_pad($directions, $fieldCount, end($directions));

        // Directions in excess are dropped, and the fields were prepended one by one, so the
        // directions have to follow that same order.
        return array_reverse(array_slice($directions, 0, $fieldCount));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1],
        ];
    }
}
