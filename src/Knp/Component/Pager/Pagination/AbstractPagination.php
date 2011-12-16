<?php

namespace Knp\Component\Pager\Pagination;

use Countable, Iterator;

abstract class AbstractPagination implements PaginationInterface, Countable, Iterator
{
    protected $currentPageNumber;
    protected $numItemsPerPage;
    protected $items = array();
    protected $totalCount;
    protected $alias;

    /**
     * {@inheritDoc}
     */
    public function rewind() {
        reset($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function current() {
        return current($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function key() {
        return key($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function next() {
        next($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function valid() {
        return key($this->items) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrentPageNumber($pageNumber)
    {
        $this->currentPageNumber = $pageNumber;
    }

    /**
     * Get currently used page number
     *
     * @return integer
     */
    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    /**
     * {@inheritDoc}
     */
    public function setItemNumberPerPage($numItemsPerPage)
    {
        $this->numItemsPerPage = $numItemsPerPage;
    }

    /**
     * Get number of items per page
     *
     * @return integer
     */
    public function getItemNumberPerPage()
    {
        return $this->numItemsPerPage;
    }

    /**
     * {@inheritDoc}
     */
    public function setTotalItemCount($numTotal)
    {
        $this->totalCount = $numTotal;
    }

    /**
     * Get total item number available
     *
     * @return integer
     */
    public function getTotalItemCount()
    {
        return $this->totalCount;
    }

    /**
     * {@inheritDoc}
     */
    public function setAlias($paginationAlias)
    {
        $this->alias = $paginationAlias;
    }

    /**
     * Get pagination alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritDoc}
     */
    public function setItems($items)
    {
        if (!is_array($items) && !$items instanceof \Traversable) {
            throw new \UnexpectedValueException("Items must be an array type");
        }
        $this->items = $items;
    }

    /**
     * Get current items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }
}