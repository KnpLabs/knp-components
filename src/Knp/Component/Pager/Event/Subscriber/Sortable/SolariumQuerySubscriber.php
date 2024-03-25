<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Exception\InvalidValueException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Solarium query sorting
 *
 * @author Marek Kalnik <marekk@theodo.fr>
 */
class SolariumQuerySubscriber implements EventSubscriberInterface
{
    private ArgumentAccessInterface $argumentAccess;

    public function __construct(ArgumentAccessInterface $argumentAccess)
    {
        $this->argumentAccess = $argumentAccess;
    }

    public function items(ItemsEvent $event): void
    {
        // Check if the result has already been sorted by another sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if (is_array($event->target) && 2 === count($event->target)) {
            $values = array_values($event->target);
            [$client, $query] = $values;

            if ($client instanceof \Solarium\Client && $query instanceof \Solarium\QueryType\Select\Query\Query) {
                $event->setCustomPaginationParameter('sorted', true);
                $sortField = $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME];
                if (null !== $sortField && $this->argumentAccess->has($sortField)) {
                    if (isset($event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST]) && !in_array($this->argumentAccess->get($sortField), $event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST])) {
                        throw new InvalidValueException("Cannot sort by: [{$this->argumentAccess->get($sortField)}] this field is not in allow list.");
                    }

                    $query->addSort($this->argumentAccess->get($sortField), $this->getSortDirection($event));
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // trigger before the pagination subscriber
            'knp_pager.items' => ['items', 1],
        ];
    }

    private function getSortDirection(ItemsEvent $event): string
    {
        $sortDir = $event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME];

        return null !== $sortDir && $this->argumentAccess->has($sortDir) &&
            strtolower($this->argumentAccess->get($sortDir)) === 'asc' ? 'asc' : 'desc';
    }
}
