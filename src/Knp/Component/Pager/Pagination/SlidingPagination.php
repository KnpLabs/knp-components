<?php

namespace Knp\Component\Pager\Pagination;

use IteratorAggregate, Countable, Traversable, ArrayIterator, Closure;

/**
 * @todo: find a way to avoid exposing private member setters
 *
 * Sliding pagination
 */
class SlidingPagination extends AbstractPagination implements Countable, IteratorAggregate
{
    /**
     * Pagination page range
     *
     * @var integer
     */
    private $range = 5;

    /**
     * Closure which is executed to render pagination
     *
     * @var Closure
     */
    public $renderer;

    public function setPageRange($range)
    {
        $this->range = intval(abs($range));
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * Returns a foreach-compatible iterator.
     *
     * @return Traversable
     */
    public function getIterator()
    {
        $items = $this->getItems();
        if (!$items instanceof Traversable) {
            $items = new ArrayIterator($items);
        }
        return $items;
    }

    /**
     * Renders the pagination
     */
    public function __toString()
    {
        $data = $this->getPaginationData();
        $output = '';
        if (!$this->renderer instanceof Closure) {
            $output = 'override in order to render a template';
        } else {
            $output = call_user_func($this->renderer, $data);
        }
        return $output;
    }

    public function getPaginationData()
    {
        $pageCount = intval(ceil($this->totalCount / $this->numItemsPerPage));
        $current = $this->currentPageNumber;

        if ($this->range > $pageCount) {
            $this->range = $pageCount;
        }

        $delta = ceil($this->range / 2);

        if ($current - $delta > $pageCount - $this->range) {
            $pages = range($pageCount - $this->range + 1);
        } else {
            if ($current - $delta < 0) {
                $delta = $current;
            }

            $offset = $current - $delta;
            $pages = range($offset + 1, $offset + $this->range);
        }

        $viewData = array(
            'last' => $pageCount,
            'current' => $current,
            'numItemsPerPage' => $this->numItemsPerPage,
            'first' => 1,
            'pageCount' => $pageCount,
            'totalCount' => $this->totalCount,
        );

        if ($current - 1 > 0) {
            $viewData['previous'] = $current - 1;
        }

        if ($current + 1 <= $pageCount) {
            $viewData['next'] = $current + 1;
        }
        $viewData['pagesInRange'] = $pages;
        $viewData['firstPageInRange'] = min($pages);
        $viewData['lastPageInRange']  = max($pages);

        if ($this->getItems() !== null) {
            $viewData['currentItemCount'] = $this->count();
            $viewData['firstItemNumber'] = (($current - 1) * $this->numItemsPerPage) + 1;
            $viewData['lastItemNumber'] = $viewData['firstItemNumber'] + $viewData['currentItemCount'] - 1;
        }

        return $viewData;
    }
}