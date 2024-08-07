<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine;

use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use Test\Fixture\Entity\Article;
use Test\Tool\BaseTestCaseORM;

final class DBALQueryBuilderTest extends BaseTestCaseORM
{
    #[Test]
    public function shouldPaginateSimpleDoctrineQuery(): void
    {
        $this->populate();
        $p = $this->getPaginatorInstance(null, null, $this->em->getConnection());

        $qb = new QueryBuilder($this->em->getConnection());
        $qb->select('*')
            ->from('Article', 'a')
        ;
        $view = $p->paginate($qb, 1, 2);

        $this->assertEquals(1, $view->getCurrentPageNumber());
        $this->assertEquals(2, $view->getItemNumberPerPage());
        $this->assertEquals(4, $view->getTotalItemCount());

        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]['title']);
        $this->assertEquals('winter', $items[1]['title']);
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
