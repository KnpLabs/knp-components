<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ORM;

use Knp\Component\Pager\PaginatorInterface;
use Test\Fixture\Entity\Article;
use Test\Tool\BaseTestCaseORM;

final class AllowListTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    public function shouldAllowListSortableFields(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $requestStack = $this->createRequestStack(['sort' => 'a.title', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $sortFieldAllowList = ['a.title'];
        $view = $p->paginate($query, 1, 10, \compact(PaginatorInterface::SORT_FIELD_ALLOW_LIST));

        $items = $view->getItems();
        self::assertCount(4, $items);
        self::assertEquals('autumn', $items[0]->getTitle());

        $requestStack = $this->createRequestStack(['sort' => 'a.id', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $view = $p->paginate($query, 1, 10, \compact(PaginatorInterface::SORT_FIELD_ALLOW_LIST));
    }

    /**
     * @test
     */
    public function shouldSortWithoutSpecificAllowList(): void
    {
        $this->populate();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $requestStack = $this->createRequestStack(['sort' => 'a.title', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals('autumn', $items[0]->getTitle());

        $requestStack = $this->createRequestStack(['sort' => 'a.id', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertEquals('summer', $items[0]->getTitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Article::class];
    }

    private function populate(): void
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
