<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Exception\InvalidValueException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ArraySubscriber implements EventSubscriberInterface
{
    /**
     * @var string the field used to sort current object array list
     */
    private string $currentSortingField;

    /**
     * @var string the sorting direction
     */
    private string $sortDirection;

    private ?PropertyAccessorInterface $propertyAccessor;

    private ArgumentAccessInterface $argumentAccess;

    public function __construct(ArgumentAccessInterface $argumentAccess, PropertyAccessorInterface $accessor = null)
    {
        if (!$accessor && class_exists(PropertyAccess::class)) {
            $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
        }

        $this->propertyAccessor = $accessor;
        $this->argumentAccess = $argumentAccess;
    }

    public function items(ItemsEvent $event): void
    {
        // Check if the result has already been sorted by another sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }
        $sortField = $event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME];
        if (!is_array($event->target) || null === $sortField || !$this->argumentAccess->has($sortField)) {
            return;
        }

        $event->setCustomPaginationParameter('sorted', true);

        if (isset($event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST]) && !in_array($this->argumentAccess->get($sortField), $event->options[PaginatorInterface::SORT_FIELD_ALLOW_LIST])) {
            throw new InvalidValueException("Cannot sort by: [{$this->argumentAccess->get($sortField)}] this field is not in allow list.");
        }

        $sortFunction = $event->options['sortFunction'] ?? [$this, 'proxySortFunction'];
        $sortField = $this->argumentAccess->get($sortField);

        // compatibility layer
        if ($sortField[0] === '.') {
            $sortField = substr($sortField, 1);
        }

        call_user_func_array($sortFunction, [
            &$event->target,
            $sortField,
            $this->getSortDirection($event->options),
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function getSortDirection(array $options): string
    {
        if (!$this->argumentAccess->has($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME])) {
            return 'desc';
        }
        $direction = $this->argumentAccess->get($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]);
        if (strtolower($direction) === 'asc') {
            return 'asc';
        }

        return 'desc';
    }

    private function proxySortFunction(mixed &$target, string $sortField, string $sortDirection): bool
    {
        $this->currentSortingField = $sortField;
        $this->sortDirection = $sortDirection;

        return usort($target, [$this, 'sortFunction']);
    }

    private function sortFunction(object|array $object1, object|array $object2): int
    {
        if (null === $this->propertyAccessor) {
            throw new \UnexpectedValueException('You need symfony/property-access component to use this sorting function');
        }

        if (!$this->propertyAccessor->isReadable($object1, $this->currentSortingField) || !$this->propertyAccessor->isReadable($object2, $this->currentSortingField)) {
            return 0;
        }

        try {
            $fieldValue1 = $this->propertyAccessor->getValue($object1, $this->currentSortingField);
        } catch (UnexpectedTypeException) {
            return -1 * $this->getSortCoefficient();
        }

        try {
            $fieldValue2 = $this->propertyAccessor->getValue($object2, $this->currentSortingField);
        } catch (UnexpectedTypeException) {
            return $this->getSortCoefficient();
        }

        if (is_string($fieldValue1)) {
            $fieldValue1 = mb_strtolower($fieldValue1);
        }

        if (is_string($fieldValue2)) {
            $fieldValue2 = mb_strtolower($fieldValue2);
        }

        if ($fieldValue1 === $fieldValue2) {
            return 0;
        }

        return ($fieldValue1 > $fieldValue2 ? 1 : -1) * $this->getSortCoefficient();
    }

    private function getSortCoefficient(): int
    {
        return $this->sortDirection === 'asc' ? 1 : -1;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1],
        ];
    }
}
