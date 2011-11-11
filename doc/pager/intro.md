# Intro to Knp Pager Component

This is a PHP 5.3 paginator with a totally diferent core concept.

How is it diferent? First of all, it uses **event dispatcher** to paginate whatever is needed.
Pagination process involves triggering events which hits the **subscribers** and if the subscriber
knows how to paginate the given object it does. Finally, some subscriber must initialize the
**pagination view** object, which will be the result of pagination request. Pagination view
can be anything which will be responsible on how to render the pagination.

## Requirements:

- Symfony EventDispatcher component
- Namespace based autoloader for this library

## Features:

- Can be customized in any way needed, etc.: pagination view, event subscribers.
- Possibility to add custom filtering, sorting functionality depending on request parameters.
- Pagination view extensions based on event.
- Paginator extensions based on events, etc.: another object pagination compatibilities.
- Supports multiple paginations during one request
- Separation of conserns, paginator is responsible for generating the pagination view only, pagination view - for displaying purposes.

## Usage examples:

### Controller

    $paginator = new Knp\Component\Pager\Paginator;
    $paginationTarget = range('a', 'u');
    // uses event subscribers to paginate $paginationTarget
    $paginationView = $paginator->paginate($paginationTarget, 2/*page*/, 10/*limit*/);
    
    // iterate paginated items
    foreach ($paginationView as $item) {
        //...
    }
    echo $paginationView; // renders pagination
    
    // overriding view rendering
    
    $paginationView->renderer = function($data) use ($template) {
        return $twig->render($template, $data);
    };
    
    echo $paginationView;
    
    // or paginate Doctrine ORM query
    
    $paginationView = $paginator->paginate($em->createQuery('SELECT a FROM Entity\Article a'), 1, 10);


