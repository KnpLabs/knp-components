<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Knp\Component\Pager\PaginatorInterface;

class ArraySubscriber implements EventSubscriberInterface
{
    /**
     * @var string the field used to sort current object array list
     */
    private $currentSortingField;

    /**
     * @var string the sorting direction
     */
    private $sortDirection;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var Request
     */
    private $request;

    public function __construct(PropertyAccessorInterface $accessor = null, RequestStack $requestStack = null)
    {
        if (!$accessor && class_exists('Symfony\Component\PropertyAccess\PropertyAccess')) {
            $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
        }

        $this->propertyAccessor = $accessor;
        $this->request = null === $requestStack ? Request::createFromGlobals() : $requestStack->getCurrentRequest();
    }

    public function items(ItemsEvent $event): void
    {
        // Check if the result has already been sorted by an other sort subscriber
        $customPaginationParameters = $event->getCustomPaginationParameters();
        if (!empty($customPaginationParameters['sorted']) ) {
            return;
        }

        if (!is_array($event->target) || !$this->request->query->has($event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME])) {
            return;
        }

        $event->setCustomPaginationParameter('sorted', true);

        if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST]) && !in_array($this->request->query->get($event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]), $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
            throw new \UnexpectedValueException("Cannot sort by: [{$this->request->query->get($event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME])}] this field is not in whitelist");
        }

        $sortFunction = isset($event->options['sortFunction']) ? $event->options['sortFunction'] : [$this, 'proxySortFunction'];
        $sortField = $this->request->query->get($event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]);

        // compatibility layer
        if ($sortField[0] === '.') {
            $sortField = substr($sortField, 1);
        }

        call_user_func_array($sortFunction, [
            &$event->target,
            $sortField,
            $this->getSortDirection($event->options)
        ]);
    }

    private function getSortDirection(array $options): string
    {
        if (!$this->request->query->has($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME])) {
            return 'desc';
        }
        $direction = $this->request->query->get($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]);
        if (strtolower($direction) === 'asc') {
            return 'asc';
        }

        return 'desc';
    }

    private function proxySortFunction(&$target, $sortField, $sortDirection) {
        $this->currentSortingField = $sortField;
        $this->sortDirection = $sortDirection;

        return usort($target, [$this, 'sortFunction']);
    }

    /**
     * @param mixed $object1 first object to compare
     * @param mixed $object2 second object to compare
     *
     * @return boolean
     */
    private function sortFunction($object1, $object2)
    {
        if (!$this->propertyAccessor) {
            throw new \UnexpectedValueException('You need symfony/property-access component to use this sorting function');
        }

        $fieldValue1 = $this->propertyAccessor->getValue($object1, $this->currentSortingField);
        $fieldValue2 = $this->propertyAccessor->getValue($object2, $this->currentSortingField);

        if (is_string($fieldValue1)) {
            $fieldValue1 = mb_strtolower($fieldValue1);
        }

        if (is_string($fieldValue2)) {
            $fieldValue2 = mb_strtolower($fieldValue2);
        }

        if ($fieldValue1 === $fieldValue2) {
            return 0;
        }

        return ($fieldValue1 > $fieldValue2 ? 1 : -1) * ($this->sortDirection === 'asc' ? 1 : -1);
    }

    public static function getSubscribedEvents()
    {
        return [
            'knp_pager.items' => ['items', 1]
        ];
    }
}
