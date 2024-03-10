<?php

namespace Knp\Component\Pager;

use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * PaginatorInterface
 */
interface PaginatorInterface
{
    public const DEFAULT_SORT_FIELD_NAME = 'defaultSortFieldName';
    public const DEFAULT_SORT_DIRECTION = 'defaultSortDirection';
    public const DEFAULT_FILTER_FIELDS = 'defaultFilterFields';
    public const SORT_FIELD_PARAMETER_NAME = 'sortFieldParameterName';
    public const SORT_FIELD_ALLOW_LIST = 'sortFieldAllowList';
    public const SORT_DIRECTION_PARAMETER_NAME = 'sortDirectionParameterName';
    public const PAGE_PARAMETER_NAME = 'pageParameterName';
    public const FILTER_FIELD_PARAMETER_NAME = 'filterFieldParameterName';
    public const FILTER_VALUE_PARAMETER_NAME = 'filterValueParameterName';
    public const FILTER_FIELD_ALLOW_LIST = 'filterFieldAllowList';
    public const DISTINCT = 'distinct';
    public const PAGE_OUT_OF_RANGE = 'pageOutOfRange';
    public const DEFAULT_LIMIT = 'defaultLimit';
    public const ODM_QUERY_OPTIONS = 'odmQueryOptions';

    public const PAGE_OUT_OF_RANGE_IGNORE = 'ignore'; // do nothing (default)
    public const PAGE_OUT_OF_RANGE_FIX = 'fix'; // replace page number out of range with max page
    public const PAGE_OUT_OF_RANGE_THROW_EXCEPTION = 'throwException'; // throw PageNumberOutOfRangeException
    public const DEFAULT_LIMIT_VALUE = 10;

    /**
     * Paginates anything (depending on event listeners)
     * into Pagination object, which is a view targeted
     * pagination object (might be aggregated helper object)
     * responsible for the pagination result representation
     *
     * @param mixed                 $target  anything what needs to be paginated
     * @param int                   $page    page number, starting from 1
     * @param int|null              $limit   number of items per page
     * @param array<string, mixed>  $options less used options:
     *                                       bool   $distinct           default true for distinction of results
     *                                       string $alias              pagination alias, default none
     *                                       array  $sortFieldAllowList sortable allow list for target fields being paginated
     *
     * @return PaginationInterface<int, mixed>
     */
    public function paginate(mixed $target, int $page = 1, int $limit = null, array $options = []): PaginationInterface;
}
