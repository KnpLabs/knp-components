<?php

namespace Knp\Component\Pager;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Exception\PageLimitInvalidException;
use Knp\Component\Pager\Exception\PageNumberInvalidException;
use Knp\Component\Pager\Exception\PageNumberOutOfRangeException;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Paginator uses event dispatcher to trigger pagination
 * lifecycle events. Subscribers are expected to paginate
 * wanted target, and finally it generates pagination view
 * which is only the result of paginator
 */
final class Paginator implements PaginatorInterface
{
    private EventDispatcherInterface $eventDispatcher;

    /**
     * Default options of paginator
     *
     * @var array<string, string|int|bool>
     */
    private array $defaultOptions = [
        self::PAGE_PARAMETER_NAME => 'page',
        self::SORT_FIELD_PARAMETER_NAME => 'sort',
        self::SORT_DIRECTION_PARAMETER_NAME => 'direction',
        self::FILTER_FIELD_PARAMETER_NAME => 'filterParam',
        self::FILTER_VALUE_PARAMETER_NAME => 'filterValue',
        self::DISTINCT => true,
        self::PAGE_OUT_OF_RANGE => self::PAGE_OUT_OF_RANGE_IGNORE,
        self::DEFAULT_LIMIT => self::DEFAULT_LIMIT_VALUE,
    ];

    private ArgumentAccessInterface $argumentAccess;

    public function __construct(EventDispatcherInterface $eventDispatcher, ArgumentAccessInterface $argumentAccess)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->argumentAccess = $argumentAccess;
    }

    /**
     * Override the default paginator options to be reused for paginations
     *
     * @param array<string, string|int|bool> $options
     */ 
    public function setDefaultPaginatorOptions(array $options): void
    {
        $this->defaultOptions = \array_merge($this->defaultOptions, $options);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return PaginationInterface<int, mixed>
     */
    public function paginate($target, int $page = 1, int $limit = null, array $options = []): PaginationInterface
    {
        if ($page <= 0) {
            throw PageNumberInvalidException::create($page);
        }

        $limit = $limit ?? (int) $this->defaultOptions[self::DEFAULT_LIMIT];
        if ($limit <= 0) {
            throw PageLimitInvalidException::create($limit);
        }

        $offset = ($page - 1) * $limit;
        $options = \array_merge($this->defaultOptions, $options);

        // normalize default sort field
        if (isset($options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME]) && is_array($options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME])) {
            $options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME] = implode('+', $options[PaginatorInterface::DEFAULT_SORT_FIELD_NAME]);
        }

        $argumentAccess = $this->argumentAccess;
        
        // default sort field and direction are set based on options (if available)
        if (isset($options[self::DEFAULT_SORT_FIELD_NAME]) && !$argumentAccess->has($options[self::SORT_FIELD_PARAMETER_NAME])) {
           $argumentAccess->set($options[self::SORT_FIELD_PARAMETER_NAME], $options[self::DEFAULT_SORT_FIELD_NAME]);

            if (!$argumentAccess->has($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME])) {
                $argumentAccess->set($options[PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME], $options[PaginatorInterface::DEFAULT_SORT_DIRECTION] ?? 'asc');
            }
        }

        // before pagination start
        $beforeEvent = new Event\BeforeEvent($this->eventDispatcher, $this->argumentAccess);
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
