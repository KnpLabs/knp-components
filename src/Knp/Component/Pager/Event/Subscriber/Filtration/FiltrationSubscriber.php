<?php

namespace Knp\Component\Pager\Event\Subscriber\Filtration;

use Knp\Component\Pager\Event\BeforeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FiltrationSubscriber implements EventSubscriberInterface
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
        // hook all standard filtration subscribers
        $dispatcher->addSubscriber(new Doctrine\ORM\QuerySubscriber($event->getRequest()));
        $dispatcher->addSubscriber(new PropelQuerySubscriber($event->getRequest()));

        $this->isLoaded = true;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.before' => ['before', 1],
        ];
    }
}
