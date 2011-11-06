<?php

namespace Knp\Component\Pager\Pagination;

// can be itterable, customizable, set through event ??

class SlidingPagination extends AbstractPagination
{
    public function render()
    {
        return 'sliding';
    }
}