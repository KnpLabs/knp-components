<?php

namespace Knp\Component\Pager\Pagination;

abstract class AbstractPagination implements PaginationInterface
{
    protected $currentPageNumber;
    protected $numItemsPerPage;
    protected $items;
    protected $totalCount;
    protected $alias;

    public function setCurrentPageNumber($pageNumber)
    {
        $this->currentPageNumber = $pageNumber;
    }

    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    public function setItemNumberPerPage($numItemsPerPage)
    {
        $this->numItemsPerPage = $numItemsPerPage;
    }

    public function getItemNumberPerPage()
    {
        return $this->numItemsPerPage;
    }

    public function setTotalItemCount($numTotal)
    {
        $this->totalCount = $numTotal;
    }

    public function getTotalItemCount()
    {
        return $this->totalCount;
    }

    public function setAlias($paginationAlias)
    {
        $this->alias = $paginationAlias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setItems($items)
    {
        $this->items = $items;
    }

    public function getItems()
    {
        return $this->items;
    }
}