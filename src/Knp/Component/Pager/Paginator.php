<?php

namespace Knp\Component\Pager;

use Knp\Component\Pager\Exception\PageNumberOutOfRangeException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Paginator uses event dispatcher to trigger pagination
 * lifecycle events. Subscribers are expected to paginate
 * wanted target and finally it generates pagination view
 * which is only the result of paginator
 */
final class Paginator implements PaginatorInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Default options of paginator
     *
     * @var array
     */
    private $defaultOptions = [
        self::PAGE_PARAMETER_NAME => 'page',
        self::SORT_FIELD_PARAMETER_NAME => 'sort',
        self::SORT_DIRECTION_PARAMETER_NAME => 'direction',
        self::FILTER_FIELD_PARAMETER_NAME => 'filterParam',
        self::FILTER_VALUE_PARAMETER_NAME => 'filterValue',
        self::DISTINCT => true,
        self::PAGE_OUT_OF_RANGE => self::PAGE_OUT_OF_RANGE_IGNORE,
        self::DEFAULT_LIMIT => self::DEFAULT_LIMIT_VALUE,
    ];

    /**
     * @var RequestStack|null
     */
    private $requestStack;

    public function __construct(EventDispatcherInterface $eventDispatcher, RequestStack $requestStack = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
    }

    /**
     * Override the default paginator options
     * to be reused for paginations
     */ 
    public function setDefaultPaginatorOptions(array $options): void
    {
        $this->defaultOptions = \array_merge($this->defaultOptions, $options);
    }

    public function paginate($target, int $page = 1, int $limit = null, array $options = []): PaginationInterface
    {
        $limit = $limit ?? $this->defaultOptions[self::DEFAULT_LIMIT];
        if ($limit <= 0 || $page <= 0) {
            throw new \LogicException("Invalid item per page number. Limit: $limit and Page: $page, must be positive non-zero integers");
        }

        $offset = ($page - 1) * $limit;
        $options = \array_merge($this->defaultOptions, $options);

        // normalize default sort field
        if (isset($options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME]) && is_array($options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME])) {
            $options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME] = implode('+', $options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME]);
        }

        $request = null === $this->requestStack ? Request::createFromGlobals() : $this->requestStack->getCurrentRequest();

        // default sort field and direction are set based on options (if available)
        if (isset($options[self::DEFAULT_SORT_FIELD_NAME]) && !$request->query->has($options[self::SORT_FIELD_PARAMETER_NAME])) {
           $request->query->set($options[self::SORT_FIELD_PARAMETER_NAME], $options[self::DEFAULT_SORT_FIELD_NAME]);

            if (!$request->query->has($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME])) {
                $request->query->set($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME], $options[PaginatorInterface::DEFAULT_SORT_DIRECTION] ?? 'asc');
            }
        }

        // before pagination start
        $beforeEvent = new Event\BeforeEvent($this->eventDispatcher, $request);
        $this->eventDispatcher->dispatch($beforeEvent, 'knp_pager.before');
        // items
        $itemsEvent = new Event\ItemsEvent($offset, $limit);
        $itemsEvent->options = &$options;
        $itemsEvent->target = &$target;
        $this->eventDispatcher->dispatch($itemsEvent, 'knp_pager.items');
        if (!$itemsEvent->isPropagationStopped()) {
            throw new \RuntimeException('One of listeners must count and slice given target');
        }
        if ($page > ceil($itemsEvent->count / $limit)) {
            $pageOutOfRangeOption = $options[PaginatorInterface::PAGE_OUT_OF_RANGE] ?? $this->defaultOptions[PaginatorInterface::PAGE_OUT_OF_RANGE];
            if ($pageOutOfRangeOption === PaginatorInterface::PAGE_OUT_OF_RANGE_FIX && $itemsEvent->count > 0) {
                // replace page number out of range with max page
                return $this->paginate($target, (int) ceil($itemsEvent->count / $limit), $limit, $options);
            }
            if ($pageOutOfRangeOption === self::PAGE_OUT_OF_RANGE_THROW_EXCEPTION && $page > 1) {
                throw new PageNumberOutOfRangeException(
                    sprintf('Page number: %d is out of range.', $page),
                    (int) ceil($itemsEvent->count / $limit)
                );
            }
        }

        // pagination initialization event
        $paginationEvent = new Event\PaginationEvent;
        $paginationEvent->target = &$target;
        $paginationEvent->options = &$options;
        $this->eventDispatcher->dispatch($paginationEvent, 'knp_pager.pagination');
        if (!$paginationEvent->isPropagationStopped()) {
            throw new \RuntimeException('One of listeners must create pagination view');
        }
        // pagination class can be different, with different rendering methods
        $paginationView = $paginationEvent->getPagination();
        $paginationView->setCustomParameters($itemsEvent->getCustomPaginationParameters());
        $paginationView->setCurrentPageNumber($page);
        $paginationView->setItemNumberPerPage($limit);
        $paginationView->setTotalItemCount($itemsEvent->count);
        $paginationView->setPaginatorOptions($options);
        $paginationView->setItems($itemsEvent->items);

        // after
        $afterEvent = new Event\AfterEvent($paginationView);
        $this->eventDispatcher->dispatch($afterEvent, 'knp_pager.after');

        return $paginationView;
    }
}
