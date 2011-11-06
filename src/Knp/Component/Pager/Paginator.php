<?php

namespace Knp\Component\Pager;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\InitPaginationEvent;
use Knp\Component\Pager\Event\CountEvent;
use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\BeforeEvent;
use Knp\Component\Pager\Event\AfterEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginateSubscriber;

class Paginator
{
    protected $eventDispatcher;

    /**
     * Initialize paginator with event dispatcher
     * Can be a service in concept
     *
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        if (is_null($this->eventDispatcher)) {
            $this->eventDispatcher = new EventDispatcher;
            $this->eventDispatcher->addSubscriber(new PaginateSubscriber);
        }
    }

    /**
     * Paginates anything (depending on event listeners)
     * into Pagination object, which is a view targeted
     * pagination object (might be aggregated helper object)
     *
     * @param mixed $target
     * @param integer $offset
     * @param integer $limit
     * @param boolean $distinct
     * @param string $alias
     * @throws \LogicException
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
        $beforeEvent = new BeforeEvent($this->eventDispatcher);
        $this->eventDispatcher->dispatch('before', $beforeEvent);
        // count
        $countEvent = new CountEvent($target, $distinct, $alias);
        $this->eventDispatcher->dispatch('count', $countEvent);
        if (!$countEvent->isPropagationStopped()) {
            throw new \RuntimeException('Some listener must count the given data');
        }
        $count = $countEvent->getCount();
        // items
        $itemsEvent = new ItemsEvent($target, $distinct, $offset, $limit, $alias);
        $this->eventDispatcher->dispatch('items', $itemsEvent);
        if (!$itemsEvent->isPropagationStopped()) {
            throw new \RuntimeException('Some listener must slice the given data');
        }
        $items = $itemsEvent->getItems();
        // pagination initialization event
        $initPaginationEvent = new InitPaginationEvent($target, $alias);
        $this->eventDispatcher->dispatch('pagination', $initPaginationEvent);
        if (!$initPaginationEvent->isPropagationStopped()) {
            throw new \RuntimeException('Some listener must create pagination view');
        }
        // pagination class can be diferent, with diferent rendering methods
        $paginationView = $initPaginationEvent->getPagination();
        $paginationView->setCurrentPageNumber($page);
        $paginationView->setItemNumberPerPage($limit);
        $paginationView->setTotalItemCount($count);
        $paginationView->setAlias($alias);
        $paginationView->setItems($items);

        // after
        $afterEvent = new AfterEvent($paginationView);
        $this->eventDispatcher->dispatch('after', $afterEvent);
        return $paginationView;
    }

    public function subscribe($subscriber)
    {
        $this->eventDispatcher->addSubscriber($subscriber);
    }

    public function connect($eventName, $listener, $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }
}