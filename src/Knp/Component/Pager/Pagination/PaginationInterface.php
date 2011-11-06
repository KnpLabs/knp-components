<?php

namespace Knp\Component\Pager\Pagination;

interface PaginationInterface
{
    function setCurrentPageNumber($pageNumber);

    function setItemNumberPerPage($numItemsPerPage);

    function setTotalItemCount($numTotal);

    function setItems($items);

    function setAlias($paginationAlias);

    function render();
}