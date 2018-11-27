<?php

namespace Knp\Component\Pager\Pagination;

use Countable, Traversable, ArrayAccess;

/**
 * Pagination interface strictly defines
 * the methods - paginator will use to populate the
 * pagination data
 */
interface PaginationInterface extends Countable, Traversable, ArrayAccess
{
    /**
     * @param int $pageNumber
     */
    public function setCurrentPageNumber(int $pageNumber): void;

    /**
     * @param int $numItemsPerPage
     */
    public function setItemNumberPerPage(int $numItemsPerPage): void;

    /**
     * @param int $numTotal
     */
    public function setTotalItemCount(int $numTotal): void;

    /**
     * @param iterable $items
     */
    public function setItems(iterable $items): void;

    /**
     * @param array $options
     */
    public function setPaginatorOptions(array $options): void;

    /**
     * @param array $parameters
     */
    public function setCustomParameters(array $parameters): void;
}
