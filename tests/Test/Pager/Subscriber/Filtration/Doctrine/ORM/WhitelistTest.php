<?php

namespace Test\Pager\Subscriber\Filtration\Doctrine\ORM;

use Knp\Component\Pager\Event\Subscriber\Filtration\FiltrationSubscriber;
use Knp\Component\Pager\ParametersResolver;
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
     */
    public function shouldFilterIfFieldInWhiteList()
    {
        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new PaginationSubscriber());
        $eventDispatcher->addSubscriber(new FiltrationSubscriber());
        $paginator = new Paginator($parametersResolver, $eventDispatcher);

        $parametersResolver
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('filterParam'), $this->equalTo(null))
            ->willReturn('a.title');
        $parametersResolver
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('filterValue'), $this->equalTo(null))
            ->willReturn('summer');

        $filterFieldWhitelist = ['a.title'];
        $view = $paginator->paginate($query, 1, 10, compact('filterFieldWhitelist'));

        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function shouldThrowExceptionIfFieldNotInWhiteList()
    {
        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new PaginationSubscriber());
        $eventDispatcher->addSubscriber(new FiltrationSubscriber());
        $paginator = new Paginator($parametersResolver, $eventDispatcher);

        $parametersResolver
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('filterParam'), $this->equalTo(null))
            ->willReturn('a.id');
        $parametersResolver
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('filterValue'), $this->equalTo(null))
            ->willReturn('summer');

        $filterFieldWhitelist = ['a.title'];
        $paginator->paginate($query, 1, 10, compact('filterFieldWhitelist'));
    }

    /**
     * @test
     */
    public function shouldFilterWithoutSpecificWhitelist()
    {
        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new PaginationSubscriber());
        $eventDispatcher->addSubscriber(new FiltrationSubscriber());
        $paginator = new Paginator($parametersResolver, $eventDispatcher);

        $parametersResolver
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('filterParam'), $this->equalTo(null))
            ->willReturn('a.title');
        $parametersResolver
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('filterValue'), $this->equalTo(null))
            ->willReturn('summer');

        $view = $paginator->paginate($query);

        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
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
