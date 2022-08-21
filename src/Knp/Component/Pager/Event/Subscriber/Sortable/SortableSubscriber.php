<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\BeforeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SortableSubscriber implements EventSubscriberInterface
{
    /**
     * Lazy-load state tracker
     */
    private bool $isLoaded = false;

    public function before(BeforeEvent $event): void
    {
        // Do not lazy-load more than once
        if ($this->isLoaded) {
            return;
        }

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $event->getEventDispatcher();
        // hook all standard sortable subscribers
        $argumentAccess = $event->getArgumentAccess();
        $dispatcher->addSubscriber(new Doctrine\ORM\QuerySubscriber($argumentAccess));
        $dispatcher->addSubscriber(new Doctrine\ODM\MongoDB\QuerySubscriber($argumentAccess));
        $dispatcher->addSubscriber(new ElasticaQuerySubscriber($argumentAccess));
        $dispatcher->addSubscriber(new PropelQuerySubscriber($argumentAccess));
        $dispatcher->addSubscriber(new SolariumQuerySubscriber($argumentAccess));
        $dispatcher->addSubscriber(new ArraySubscriber($argumentAccess));

        $this->isLoaded = true;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.before' => ['before', 1],
        ];
    }
}
