<?php

namespace Knp\Component\Pager\Pagination;

use Iterator;

/**
 * @template TKey
 * @template TValue
 *
 * @template-implements PaginationInterface<TKey, TValue>
 */
abstract class AbstractPagination implements Iterator, PaginationInterface
{
    protected ?int $currentPageNumber = null;
    protected ?int $numItemsPerPage = null;
    /** @var iterable<int, mixed>|object */
    protected iterable $items = [];
    protected ?int $totalCount = null;
    /** @var array<string, mixed>  */
    protected ?array $paginatorOptions = null;
    /** @var array<string, mixed>  */
    protected ?array $customParameters = null;

    public function rewind(): void
    {
        if (is_object($this->items)) {
            $items = get_mangled_object_vars($this->items);
            reset($items);
            $this->items = new \ArrayObject($items);
        } else {
            reset($this->items);
        }
    }

    public function current(): mixed
    {
        return current($this->items);
    }

    public function key(): string|int|null
    {
        if (is_object($this->items)) {
            $items = get_mangled_object_vars($this->items);

            return key($items);
        }

        return key($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function valid(): bool
    {
        return null !== $this->key();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function setCustomParameters(array $parameters): void
    {
        $this->customParameters = $parameters;
    }

    public function getCustomParameter(string $name): mixed
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

    public function getPaginatorOption(string $name): mixed
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
     */
    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * @param string|int|float|bool|null $offset
     */
    public function offsetSet($offset, mixed $value): void
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
