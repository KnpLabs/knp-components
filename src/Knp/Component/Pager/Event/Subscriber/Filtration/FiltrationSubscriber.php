<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\BeforeEvent;

class FiltrationSubscriber implements EventSubscriberInterface
{
    public function before(BeforeEvent $event)
    {
        $disp = $event->getEventDispatcher();
        // hook all standard filtration subscribers
        $disp->addSubscriber(new Doctrine\ORM\QuerySubscriber());
        $disp->addSubscriber(new PropelQuerySubscriber());
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.before' => array('before', 1),
        );
    }
}
