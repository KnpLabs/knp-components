<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ORM\QuerySubscriber as Sortable;
use Test\Fixture\Entity\Article;

class QueryTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldSortSimpleDoctrineQuery()
    {
        $this->populate();

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new PaginationSubscriber);
        $dispatcher->addSubscriber(new Sortable);
        $p = new Paginator($dispatcher);

        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());
        $this->assertEquals('summer', $items[2]->getTitle());
        $this->assertEquals('winter', $items[3]->getTitle());

        $_GET['direction'] = 'desc';
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals(4, count($items));
        $this->assertEquals('winter', $items[0]->getTitle());
        $this->assertEquals('summer', $items[1]->getTitle());
        $this->assertEquals('spring', $items[2]->getTitle());
        $this->assertEquals('autumn', $items[3]->getTitle());

        $this->assertEquals(6, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $this->assertEquals('SELECT DISTINCT a0_.id AS id0, a0_.title AS title1 FROM Article a0_ ORDER BY a0_.title ASC LIMIT 10 OFFSET 0', $executed[1]);
        $this->assertEquals('SELECT DISTINCT a0_.id AS id0, a0_.title AS title1 FROM Article a0_ ORDER BY a0_.title DESC LIMIT 10 OFFSET 0', $executed[4]);
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    function shouldValidateSortableParameters()
    {
        $_GET['sort'] = '"a.title\'';
        $_GET['direction'] = 'asc';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);
    }

    /**
     * @test
     */
    function shouldWorkWithInitialPaginatorEventDispatcher()
    {
        $this->populate();
        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $query = $this
            ->em
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $p = new Paginator;
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertTrue($view instanceof SlidingPagination);

        $this->assertEquals(3, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $this->assertEquals('SELECT DISTINCT a0_.id AS id0, a0_.title AS title1 FROM Article a0_ ORDER BY a0_.title ASC LIMIT 10 OFFSET 0', $executed[1]);
    }

    /**
     * @test
     */
    function shouldNotExecuteExtraQueriesWhenCountIsZero()
    {
        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $p = new Paginator;
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertTrue($view instanceof SlidingPagination);

        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
    }

    protected function getUsedEntityFixtures()
    {
        return array('Test\Fixture\Entity\Article');
    }

    private function populate()
    {
        $em = $this->getMockSqliteEntityManager();
        $summer = new Article;
        $summer->setTitle('summer');

        $winter = new Article;
        $winter->setTitle('winter');

        $autumn = new Article;
        $autumn->setTitle('autumn');

        $spring = new Article;
        $spring->setTitle('spring');

        $em->persist($summer);
        $em->persist($winter);
        $em->persist($autumn);
        $em->persist($spring);
        $em->flush();
    }
}