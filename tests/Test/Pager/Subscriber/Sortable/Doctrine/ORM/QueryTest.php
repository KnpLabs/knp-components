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
    function shouldPaginateSimpleDoctrineQuery()
    {
        $this->populate();

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new PaginationSubscriber);
        $dispatcher->addSubscriber(new Sortable);
        $p = new Paginator($dispatcher);

        $_GET['sort'] = 'a.title';
        $_GET['direction'] = 'asc';
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