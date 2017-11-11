<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ORM;

use Knp\Component\Pager\ParametersResolver;
use Knp\Component\Pager\PaginatorInterface;
use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Test\Fixture\Entity\Article;

class WhitelistTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldSortIfFieldIsInWhiteList()
    {
        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $parametersResolver
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('sort'), $this->equalTo(null))
            ->willReturn('a.title');
        $parametersResolver
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('direction'), $this->equalTo('asc'))
            ->willReturn('asc');

        $sortFieldWhitelist = ['a.title'];
        $view = $paginator->paginate($query, 1, 10, compact(PaginatorInterface::SORT_FIELD_WHITELIST));

        $items = $view->getItems();
        $this->assertCount(4, $items);
        $this->assertEquals('autumn', $items[0]->getTitle());
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    function shouldThrowExceptionIfFieldIsNotInWhiteList()
    {
        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $parametersResolver
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('sort'), $this->equalTo(null))
            ->willReturn('a.id');

        $parametersResolver
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('direction'), $this->equalTo('asc'))
            ->willReturn('asc');

        $sortFieldWhitelist = ['a.title'];
        $paginator->paginate($query, 1, 10, compact(PaginatorInterface::SORT_FIELD_WHITELIST));
    }

    /**
     * @test
     */
    function shouldSortIfNoWhiteListProvided()
    {
        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $parametersResolver
            ->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('sort'), $this->equalTo(null))
            ->willReturn('a.title');

        $parametersResolver
            ->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('direction'), $this->equalTo('asc'))
            ->willReturn('asc');

        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertEquals('autumn', $items[0]->getTitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Article::class];
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
