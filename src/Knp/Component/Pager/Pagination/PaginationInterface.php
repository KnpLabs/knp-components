<?php

namespace Knp\Component\Pager\Pagination;

use ArrayAccess, Countable, Traversable;

/**
 * Pagination interface strictly defines
 * the methods - paginator will use to populate the
 * pagination data
 */
interface PaginationInterface extends Countable, Traversable, ArrayAccess
{
    public function setCurrentPageNumber(int $pageNumber): void;

    /**
     * Get currently used page number
     */
    public function getCurrentPageNumber(): int;

    public function setItemNumberPerPage(int $numItemsPerPage): void;

    /**
     * Get number of items per page
     */
    public function getItemNumberPerPage(): int;

    public function setTotalItemCount(int $numTotal): void;

    /**
     * Get total item number available
     */
    public function getTotalItemCount(): int;

    public function setItems(iterable $items): void;

    /**
     * Get current items
     */
    public function getItems(): iterable;

    /**
     * @param array $options
     */
    public function setPaginatorOptions(array $options): void;

    /**
     * Get pagination alias
     *
     * @return mixed
     */
    public function getPaginatorOption(string $name);

    /**
     * @param array $parameters
     */
    public function setCustomParameters(array $parameters): void;

    /**
     * Return custom parameter
     * 
     * @return mixed
     */
    public function getCustomParameter(string $name);
}
