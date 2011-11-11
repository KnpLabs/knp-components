<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\BeforeEvent;
use ReflectionClass;

class SortableSubscriber implements EventSubscriberInterface
{
    public function before(BeforeEvent $event)
    {
        $disp = $event->getEventDispatcher();
        // hook all standard sortable subscribers
        $disp->addSubscriber(new Doctrine\ORM\QuerySubscriber);
        $disp->addSubscriber(new Doctrine\ODM\MongoDB\QuerySubscriber);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'before' => array('before', 1)
        );
    }
}