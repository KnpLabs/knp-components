## Configuration


Some subscribers will take into account some options.  
These options can be passed as the 4th argument of `Paginator::paginate()`.

For example, Doctrine ORM subscriber will generate different sql queries if the `distinct` options changes.


The list of existing options are:

| name                       | type   | default value | subscribers                                     |
| -------------------------- | ------ | ------------- | ----------------------------------------------- |
| wrap-queries               | bool   | false         | orm QuerySubscriber, orm QueryBuilderSubscriber |
| distinct                   | bool   | true          | QuerySubscriber, QueryBuilderSubscriber         |
| pageParameterName          | string | page          | SortableSubscriber                              |
| defaultSortFieldName       | string |               | SortableSubscriber                              |
| defaultSortDirection       | string | asc           | SortableSubscriber                              |
| sortFieldWhitelist         | array  | []            | SortableSubscriber                              |
| sortFieldParameterName     | string | sort          | SortableSubscriber                              |
| sortDirectionParameterName | string | sort          | SortableSubscriber                              |
| filterFieldParameterName   | string | filterParam   | FiltrationSubscriber                            |
| filterValueParameterName   | string | filterValue   | FiltrationSubscriber                            |


## Noticeable behaviors of some options

### `distinct` 

If set to true, will add a distinct sql keyword on orm queries to ensuire unicity of counted results


### `wrap-queries` 

If set to true, will take advantage of doctrine 2.3 output walkers by using subqueries to handle composite keys and HAVING queries.  
This can considerably impact performances depending on the query and the platform, you will have to consider it at some point.

