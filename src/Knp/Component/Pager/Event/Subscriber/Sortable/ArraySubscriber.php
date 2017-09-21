<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\ItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ArraySubscriber implements EventSubscriberInterface
{
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
        if (!is_array($event->target)) {
            return;
        }

        $parametersResolver = $event->getParametersResolver();
        $field = $parametersResolver->get(
            $event->options['sortFieldParameterName'],
            $event->options['defaultSortFieldName'] ?? null
        );

        if ($field === null) {
            return;
        }

        $direction = $parametersResolver->get(
            $event->options['sortDirectionParameterName'],
            $event->options['defaultSortDirection'] ?? 'asc'
        );

        $whiteList = $event->options['sortFieldWhitelist'] ?? [];
        if (count($whiteList) !== 0 && !in_array($field, $whiteList, true)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot sort by: [%s] this field is not in whitelist', $field)
            );
        }

        // compatibility layer
        if ($field[0] === '.') {
            $field = substr($field, 1);
        }

        if (isset($event->options['sortFunction'])) {
            $event->options['sortFunction']($event->target, $field, $direction);

            return;
        }

        $this->sort($event->target, $field, $direction);
    }

    private function sort(&$target, $field, $direction)
    {
        if (!$this->propertyAccessor) {
            throw new \UnexpectedValueException('You need symfony/property-access component to use this sorting function');
        }

        usort($target, function ($object1, $object2) use ($field, $direction) {
            $fieldValue1 = $this->propertyAccessor->getValue($object1, $field);
            $fieldValue2 = $this->propertyAccessor->getValue($object2, $field);

            if (is_string($fieldValue1)) {
                $fieldValue1 = mb_strtolower($fieldValue1);
            }

            if (is_string($fieldValue2)) {
                $fieldValue2 = mb_strtolower($fieldValue2);
            }

            if ($fieldValue1 === $fieldValue2) {
                return 0;
            }

            return ($fieldValue1 <=> $fieldValue2) * ($direction === 'asc' ? 1 : -1);
        });
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1]
        ];
    }
}
