<?php

namespace Test\Pager\Subscriber\Filtration\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Filtration\FiltrationSubscriber as Filtration;
use Test\Fixture\Entity\Article;

class WhitelistTest extends BaseTestCaseORM
{
    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function shouldWhitelistFiltrationFields()
    {
        $this->populate();
        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = 'summer';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $filterFieldWhitelist = array('a.title');
        $view = $p->paginate($query, 1, 10, compact('filterFieldWhitelist'));

        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $_GET['filterParam'] = 'a.id';
        $view = $p->paginate($query, 1, 10, compact('filterFieldWhitelist'));
    }

    /**
     * @test
     */
    public function shouldFilterWithoutSpecificWhitelist()
    {
        $this->populate();
        $_GET['filterParam'] = 'a.title';
        $_GET['filterValue'] = 'autumn';
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $p = new Paginator($dispatcher);
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals('autumn', $items[0]->getTitle());

        $_GET['filterParam'] = 'a.id';
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals(0, count($items));
    }

    protected function getUsedEntityFixtures()
    {
        return array(Article::class);
    }

    private function populate()
    {
        $em = $this->getMockSqliteEntityManager();
        $summer = new Article();
        $summer->setTitle('summer');

        $winter = new Article();
        $winter->setTitle('winter');

        $autumn = new Article();
        $autumn->setTitle('autumn');

        $spring = new Article();
        $spring->setTitle('spring');

        $em->persist($summer);
        $em->persist($winter);
        $em->persist($autumn);
        $em->persist($spring);
        $em->flush();
    }
}
