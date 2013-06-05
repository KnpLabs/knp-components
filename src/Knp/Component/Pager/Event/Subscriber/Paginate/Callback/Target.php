<?php

namespace Knp\Component\Pager\Event\Subscriber\Paginate\Callback;


class Target {

    protected $countCallback;
    protected $itemCallback;

    public function __construct($countCallback, $itemCallback)
    {
        if (!is_callable($countCallback)) throw new \InvalidArgumentException("'count' argument is not callable");
        if (!is_callable($itemCallback)) throw new \InvalidArgumentException("'itemCallback' argument is not callable");

        $this->countCallback = $countCallback;
        $this->itemCallback = $itemCallback;
    }

    public function count()
    {
        return call_user_func_array($this->countCallback, []);
    }

    public function items($limit, $offset)
    {
        return call_user_func_array($this->itemCallback, [$limit, $offset]);
    }

}