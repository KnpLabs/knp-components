<?php

namespace Knp\Component\Pager;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event;

/**
 * Paginator uses event dispatcher to trigger pagination
 * lifecycle events. Subscribers are expected to paginate
 * wanted target and finally it generates pagination view
 * which is only the result of paginator
 */
class Paginator
{
    /**
     * @var Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * Initialize paginator with event dispatcher
     * Can be a service in concept. By default it
     * hooks standard pagination subscriber
     *
     * @param Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        if (is_null($this->eventDispatcher)) {
            $this->eventDispatcher = new EventDispatcher;
            $this->eventDispatcher->addSubscriber(new PaginationSubscriber);
        }
    }

    /**
     * Paginates anything (depending on event listeners)
     * into Pagination object, which is a view targeted
     * pagination object (might be aggregated helper object)
     * responsible for the pagination result representation
     *
     * @param mixed $target - anything what needs to be paginated
     * @param integer $page - page number, starting from 1
     * @param integer $limit - number of items per page
     * @param boolean $distinct - distinction of results if possible
     * @param string $alias - pagination alias, allows multiple paginations per request
     * @throws LogicException
     * @return Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function paginate($target, $page = 1, $limit = 10, $distinct = true, $alias = '')
    {
        $limit = intval(abs($limit));
        if (!$limit) {
            throw new \LogicException("Invalid item per page number, must be a positive number");
        }
        $offset = abs($page - 1) * $limit;
        // before pagination start
        $beforeEvent = new Event\BeforeEvent($this->eventDispatcher);
        $this->eventDispatcher->dispatch('before', $beforeEvent);
        // count
        $countEvent = new Event\CountEvent($target, $distinct, $alias);
        $this->eventDispatcher->dispatch('count', $countEvent);
        if (!$countEvent->isPropagationStopped()) {
            throw new \RuntimeException('Some listener must count the given data');
        }
        $count = $countEvent->getCount();
        // items
        $itemsEvent = new Event\ItemsEvent($target, $distinct, $offset, $limit, $alias);
        $this->eventDispatcher->dispatch('items', $itemsEvent);
        if (!$itemsEvent->isPropagationStopped()) {
            throw new \RuntimeException('Some listener must slice the given data');
        }
        $items = $itemsEvent->getItems();
        // pagination initialization event
        $paginationEvent = new Event\PaginationEvent($target, $alias);
        $this->eventDispatcher->dispatch('pagination', $paginationEvent);
        if (!$paginationEvent->isPropagationStopped()) {
            throw new \RuntimeException('Some listener must create pagination view');
        }
        // pagination class can be diferent, with diferent rendering methods
        $paginationView = $paginationEvent->getPagination();
        $paginationView->setCurrentPageNumber($page);
        $paginationView->setItemNumberPerPage($limit);
        $paginationView->setTotalItemCount($count);
        $paginationView->setAlias($alias);
        $paginationView->setItems($items);

        // after
        $afterEvent = new Event\AfterEvent($paginationView);
        $this->eventDispatcher->dispatch('after', $afterEvent);
        return $paginationView;
    }

    /**
     * Hooks in the given event subscriber
     *
     * @param Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
     */
    public function subscribe(EventSubscriberInterface $subscriber)
    {
        $this->eventDispatcher->addSubscriber($subscriber);
    }

    /**
     * Hooks the listener to the given event name
     *
     * @param string $eventName
     * @param object $listener
     * @param integer $priority
     */
    public function connect($eventName, $listener, $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }
}