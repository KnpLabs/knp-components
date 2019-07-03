<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Callback;

class CallbackPagination
{
    /**
     * @var callable
     */
    private $count;

    /**
     * @var callable
     */
    private $items;

    /**
     * @param callable $count
     * @param callable $items
     */
    public function __construct(callable $count, callable $items)
    {
        $this->count = $count;
        $this->items = $items;
    }

    /**
     * @return int
     */
    public function getPaginationCount(): int
    {
        return call_user_func($this->count);
    }

    /**
     * @param $offset
     * @param $limit
     * @return array
     */
    public function getPaginationItems($offset, $limit): array
    {
        return call_user_func_array($this->items, array($offset, $limit));
    }
}
