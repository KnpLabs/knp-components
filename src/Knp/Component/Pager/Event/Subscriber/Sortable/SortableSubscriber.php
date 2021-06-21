<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Knp\Component\Pager\Event\BeforeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SortableSubscriber implements EventSubscriberInterface
{
    /**
     * Lazy-load state tracker
     * @var bool
     */
    private $isLoaded = false;

    public function before(BeforeEvent $event): void
    {
        // Do not lazy-load more than once
        if ($this->isLoaded) {
            return;
        }

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $event->getEventDispatcher();
        // hook all standard sortable subscribers
        $request = $event->getRequest();
        $dispatcher->addSubscriber(new Doctrine\ORM\QuerySubscriber($request));
        $dispatcher->addSubscriber(new Doctrine\ODM\MongoDB\QuerySubscriber($request));
        $dispatcher->addSubscriber(new ElasticaQuerySubscriber($request));
        $dispatcher->addSubscriber(new PropelQuerySubscriber($request));
        $dispatcher->addSubscriber(new SolariumQuerySubscriber($request));
        $dispatcher->addSubscriber(new ArraySubscriber($request));

        $this->isLoaded = true;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.before' => ['before', 1],
        ];
    }
}
