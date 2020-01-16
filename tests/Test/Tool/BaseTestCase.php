<?php

namespace Test\Tool;

use Knp\Component\Pager\Event\Subscriber\Sortable\SortableSubscriber;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Test\Mock\PaginationSubscriber;

/**
 * Base test case
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
    }

    protected function getPaginatorInstance(?RequestStack $requestStack = null): Paginator
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new SortableSubscriber());

        return new Paginator($dispatcher, $requestStack);
    }

    protected function createRequestStack(array $params): RequestStack
    {
        $request = new Request($params);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
