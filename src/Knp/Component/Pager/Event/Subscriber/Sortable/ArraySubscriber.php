<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

    public function __construct(PropertyAccessorInterface $accessor = null)
    {
        if (!$accessor && class_exists('Symfony\Component\PropertyAccess\PropertyAccess')) {
            $accessor = PropertyAccess::createPropertyAccessorBuilder()->enableMagicCall()->getPropertyAccessor();
        }

        $this->propertyAccessor = $accessor;
    }

    public function items(ItemsEvent $event)
    {
        if (!is_array($event->target) || empty($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]])) {
            return;
        }

        if (isset($event->options[PaginatorInterface::SORT_FIELD_WHITELIST]) && !in_array($_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]], $event->options[PaginatorInterface::SORT_FIELD_WHITELIST])) {
            throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]]}] this field is not in whitelist");
        }

        $sortFunction = isset($event->options['sortFunction']) ? $event->options['sortFunction'] : array($this, 'proxySortFunction');
        $sortField = $_GET[$event->options[PaginatorInterface::SORT_FIELD_PARAMETER_NAME]];

        // compatibility layer
        if ($sortField[0] === '.') {
            $sortField = substr($sortField, 1);
        }

        call_user_func_array($sortFunction, array(
            &$event->target,
            $sortField,
            isset($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) && strtolower($_GET[$event->options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME]]) === 'asc' ? 'asc' : 'desc'
        ));
    }

    private function proxySortFunction(&$target, $sortField, $sortDirection) {
        $this->currentSortingField = $sortField;
        $this->sortDirection = $sortDirection;

        return usort($target, array($this, 'sortFunction'));
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

        // @see https://bugs.php.net/bug.php?id=50688
        // To avoid this problem in PHP 5.x we can't use PropertyAccessor::isReadable()
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            if (is_object($object1)) {
                // snake to camelcase
                $sortFieldName = ucfirst(strtr(ucwords(strtr($this->currentSortingField, array('_' => ' '))), array(' ' => '')));
                $currentSortingFieldGetter = 'get' . $sortFieldName;
                // Getter detection
                $class = new \ReflectionClass(get_class($object1));
                if (!$class->hasMethod($currentSortingFieldGetter)) {
                    return 0;
                }
            }
        } else {
            if (!$this->propertyAccessor->isReadable($object1, $this->currentSortingField) ||
                !$this->propertyAccessor->isReadable($object2, $this->currentSortingField)
            ) {
                return 0;
            }
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
        return array(
            'knp_pager.items' => array('items', 1)
        );
    }
}
