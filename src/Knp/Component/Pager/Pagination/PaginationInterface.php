<?php

namespace Knp\Component\Pager\Pagination;

use ArrayAccess, Countable, Traversable;

/**
 * Pagination interface strictly defines
 * the methods - paginator will use to populate the
 * pagination data
 *
 * @template TKey
 * @template TValue
 *
 * @template-extends Traversable<TKey, TValue>
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

    /**
     * @param iterable<TKey, TValue> $items
     */
    public function setItems(iterable $items): void;

    /**
     * Get current items
     *
     * @return iterable<TKey, TValue>
     */
    public function getItems(): iterable;

    /**
     * @param array<string, mixed> $options
     */
    public function setPaginatorOptions(array $options): void;

    /**
     * Get pagination alias
     */
    public function getPaginatorOption(string $name): mixed;

    /**
     * @param array<string, mixed> $parameters
     */
    public function setCustomParameters(array $parameters): void;

    /**
     * Return custom parameter
     */
    public function getCustomParameter(string $name): mixed;
}
