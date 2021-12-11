<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate;

use Knp\Component\Pager\Event\BeforeEvent;
use Knp\Component\Pager\Event\PaginationEvent;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaginationSubscriber implements EventSubscriberInterface
{
    /**
     * Lazy-load state tracker
     */
    private bool $isLoaded = false;

    public function pagination(PaginationEvent $event): void
    {
        $event->setPagination(new SlidingPagination);
        $event->stopPropagation();
    }

    public function before(BeforeEvent $event): void
    {
        // Do not lazy-load more than once
        if ($this->isLoaded) {
            return;
        }

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $event->getEventDispatcher();
        // hook all standard subscribers
        $dispatcher->addSubscriber(new ArraySubscriber);
        $dispatcher->addSubscriber(new Callback\CallbackSubscriber);
        $dispatcher->addSubscriber(new Doctrine\ORM\QueryBuilderSubscriber);
        $dispatcher->addSubscriber(new Doctrine\ORM\QuerySubscriber);
        $dispatcher->addSubscriber(new Doctrine\ODM\MongoDB\QueryBuilderSubscriber);
        $dispatcher->addSubscriber(new Doctrine\ODM\MongoDB\QuerySubscriber);
        $dispatcher->addSubscriber(new Doctrine\ODM\PHPCR\QueryBuilderSubscriber);
        $dispatcher->addSubscriber(new Doctrine\ODM\PHPCR\QuerySubscriber);
        $dispatcher->addSubscriber(new Doctrine\CollectionSubscriber);
        $dispatcher->addSubscriber(new Doctrine\DBALQueryBuilderSubscriber);
        $dispatcher->addSubscriber(new PropelQuerySubscriber);
        $dispatcher->addSubscriber(new SolariumQuerySubscriber());
        $dispatcher->addSubscriber(new ElasticaQuerySubscriber());

        $this->isLoaded = true;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.before' => ['before', 0],
            'knp_pager.pagination' => ['pagination', 0],
        ];
    }
}
