<?php

namespace Knp\Component\Pager\Pagination;

use Iterator;

abstract class AbstractPagination implements Iterator, PaginationInterface
{
    protected $currentPageNumber;
    protected $numItemsPerPage;
    protected $items = [];
    protected $totalCount;
    protected $paginatorOptions;
    protected $customParameters;

    public function rewind(): void
    {
        reset($this->items);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * @return bool|float|int|string|null
     */
    public function key()
    {
        return key($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function valid(): bool
    {
        return key($this->items) !== null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function setCustomParameters(array $parameters): void
    {
        $this->customParameters = $parameters;
    }

    /**
     * @return mixed|null
     */
    public function getCustomParameter(string $name)
    {
        return $this->customParameters[$name] ?? null;
    }

    public function setCurrentPageNumber(int $pageNumber): void
    {
        $this->currentPageNumber = $pageNumber;
    }

    public function getCurrentPageNumber(): int
    {
        return $this->currentPageNumber;
    }

    public function setItemNumberPerPage(int $numItemsPerPage): void
    {
        $this->numItemsPerPage = $numItemsPerPage;
    }

    public function getItemNumberPerPage(): int
    {
        return $this->numItemsPerPage;
    }

    public function setTotalItemCount(int $numTotal): void
    {
        $this->totalCount = $numTotal;
    }

    public function getTotalItemCount(): int
    {
        return $this->totalCount;
    }

    public function setPaginatorOptions(array $options): void
    {
        $this->paginatorOptions = $options;
    }

    /**
     * @return mixed|null
     */
    public function getPaginatorOption(string $name)
    {
        return $this->paginatorOptions[$name] ?? null;
    }

    public function setItems(iterable $items): void
    {
        $this->items = $items;
    }

    public function getItems(): iterable
    {
        return $this->items;
    }

    /**
     * @param string|int|float|bool|null $offset
     */
    public function offsetExists($offset): bool
    {
        if ($this->items instanceof \ArrayIterator) {
            return array_key_exists($offset, iterator_to_array($this->items));
        }

        return array_key_exists($offset, $this->items);
    }

    /**
     * @param string|int|float|bool|null $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * @param string|int|float|bool|null $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * @param string|int|float|bool|null $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}
