<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Test\Fixture\Entity\Composite;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber\UsesPaginator;
use Doctrine\ORM\Query;

class CompositeKeyTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldBeHandledByQueryHintByPassingCount()
    {
        $p = new Paginator;
        $em = $this->getMockSqliteEntityManager();

        $count = $em
            ->createQuery('SELECT COUNT(c) FROM Test\Fixture\Entity\Composite c')
            ->getSingleScalarResult()
        ;

        $query = $em
            ->createQuery('SELECT c FROM Test\Fixture\Entity\Composite c')
            ->setHint('knp_paginator.count', $count)
        ;
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, false);
        $view = $p->paginate($query, 1, 10, array('wrap-queries' => true));

        $items = $view->getItems();
        $this->assertEquals(0, count($items));
    }

    protected function getUsedEntityFixtures()
    {
        return array('Test\Fixture\Entity\Composite');
    }
}
