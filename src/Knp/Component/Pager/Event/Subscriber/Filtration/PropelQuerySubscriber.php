<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Exception\InvalidValueException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PropelQuerySubscriber implements EventSubscriberInterface
{
    private ArgumentAccessInterface $argumentAccess;

    public function __construct(ArgumentAccessInterface $argumentAccess)
    {
        $this->argumentAccess = $argumentAccess;
    }

    public function items(ItemsEvent $event): void
    {
        $query = $event->target;
        if ($query instanceof \ModelCriteria) {
            if (!$this->argumentAccess->has($event->options[PaginatorInterface::FILTER_VALUE_PARAMETER_NAME])) {
                return;
            }
            if ($this->argumentAccess->has($event->options[PaginatorInterface::FILTER_FIELD_PARAMETER_NAME])) {
                $columns = $this->argumentAccess->get($event->options[PaginatorInterface::FILTER_FIELD_PARAMETER_NAME]);
            } elseif (!empty($event->options[PaginatorInterface::DEFAULT_FILTER_FIELDS])) {
                $columns = $event->options[PaginatorInterface::DEFAULT_FILTER_FIELDS];
            } else {
                return;
            }
            if (is_string($columns) && str_contains($columns, ',')) {
                $columns = explode(',', $columns);
            }
            $columns = (array) $columns;
            if (isset($event->options[PaginatorInterface::FILTER_FIELD_ALLOW_LIST])) {
                foreach ($columns as $column) {
                    if (!in_array($column, $event->options[PaginatorInterface::FILTER_FIELD_ALLOW_LIST])) {
                        throw new InvalidValueException("Cannot filter by: [$column] this field is not in allow list");
                    }
                }
            }
            $value = $this->argumentAccess->get($event->options[PaginatorInterface::FILTER_VALUE_PARAMETER_NAME]);
            $criteria = \Criteria::EQUAL;
            if (str_contains($value, '*')) {
                $value = str_replace('*', '%', $value);
                $criteria = \Criteria::LIKE;
            }
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    $query->where($column.$criteria.'?', $value);
                } else {
                    $query->{'filterBy'.$column}($value, $criteria);
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 0],
        ];
    }
}
