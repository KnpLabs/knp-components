<?php

namespace Test\Tool;

use Doctrine\DBAL\Connection;
use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\ArgumentAccess\RequestArgumentAccess;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\SortableSubscriber;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base test case
 */
abstract class BaseTestCase extends TestCase
{
    protected function getPaginatorInstance(
        ?RequestStack $requestStack = null,
        ?EventDispatcher $dispatcher = null,
        ?Connection $connection = null
    ): Paginator {
        if (null === $dispatcher) {
            $dispatcher = new EventDispatcher();
            $dispatcher->addSubscriber(new PaginationSubscriber());
            $dispatcher->addSubscriber(new SortableSubscriber());
        }
        if (null !== $requestStack) {
            $accessor = new RequestArgumentAccess($requestStack);
        } else {
            $accessor = $this->createMock(ArgumentAccessInterface::class);
        }

        return new Paginator($dispatcher, $accessor, $connection);
    }

    protected function createRequestStack(array $params): RequestStack
    {
        $request = new Request($params);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
