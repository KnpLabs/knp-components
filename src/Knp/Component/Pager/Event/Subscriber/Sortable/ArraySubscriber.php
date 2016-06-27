<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use \ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Doctrine\ORM\Query;

class ArraySubscriber implements EventSubscriberInterface
{

    /**
     * @var string the field used to sort current object array list
     */
    private $currentSortingFieldGetter;

    /**
     * @var string the sorting direction
     */
    private $sortDirection;

    public function items(ItemsEvent $event)
    {
        if (is_array($event->target) && count($event->target) > 1)
        {
            if (isset($_GET[$event->options['sortFieldParameterName']])) {
                $this->sortDirection = isset($_GET[$event->options['sortDirectionParameterName']]) && strtolower($_GET[$event->options['sortDirectionParameterName']]) === 'asc' ? 'asc' : 'desc';

                // TODO add whitelist
//                if (isset($event->options['sortFieldWhitelist'])) {
//                    if (!in_array($_GET[$event->options['sortFieldParameterName']], $event->options['sortFieldWhitelist'])) {
//                        throw new \UnexpectedValueException("Cannot sort by: [{$_GET[$event->options['sortFieldParameterName']]}] this field is not in whitelist");
//                    }
//                }

                $sortFieldParameterName = explode('.', $_GET[$event->options['sortFieldParameterName']]);
                if(isset($sortFieldParameterName[1])) {
                    // Capitalize first letter in order to prepare getter construction
                    $sortFieldName = ucfirst($sortFieldParameterName[1]);

                    $this->currentSortingFieldGetter = "get{$sortFieldName}";

                    // Getter detection
                    $class = new ReflectionClass(get_class($event->target[0]));
                    if($class->hasMethod($this->currentSortingFieldGetter)) {
                        // Sort
                        usort($event->target, array($this, "sort" . ucfirst($this->sortDirection)));
                    }
                }
            }
        }
    }

    /**
     * @param $object1 first object to compare
     * @param $object2 second object to compare
     */
    private function sortAsc($object1, $object2)
    {
        $fieldValue1 = strtolower($object1->{$this->currentSortingFieldGetter}());
        $fieldValue2 = strtolower($object2->{$this->currentSortingFieldGetter}());
        if ($fieldValue1 == $fieldValue2) {
            return 0;
        }
        return ($fieldValue1 > $fieldValue2) ? +1 : -1;
    }

    /**
     * @param $object1 first object to compare
     * @param $object2 second object to compare
     */
    private function sortDesc($object1, $object2)
    {
        $fieldValue1 = strtolower($object1->{$this->currentSortingFieldGetter}());
        $fieldValue2 = strtolower($object2->{$this->currentSortingFieldGetter}());
        if ($fieldValue1 == $fieldValue2) {
            return 0;
        }
        return ($fieldValue1 > $fieldValue2) ? -1 : +1;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 1)
        );
    }
}