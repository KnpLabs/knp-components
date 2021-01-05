<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Knp\Component\Pager\PaginatorInterface;
use Test\Fixture\Document\Article;
use Test\Tool\BaseTestCaseMongoODM;

final class AllowListTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     */
    public function shouldAllowListSortableFields(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->populate();
        $query = $this->dm
            ->createQueryBuilder(Article::class)
            ->getQuery()
        ;

        $requestStack = $this->createRequestStack(['sort' => 'title', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $sortFieldAllowList = ['title'];
        $view = $p->paginate($query, 1, 10, \compact(PaginatorInterface::SORT_FIELD_ALLOW_LIST));

        $items = \array_values($view->getItems());
        self::assertCount(4, $items);
        self::assertEquals('autumn', $items[0]->getTitle());

        $requestStack = $this->createRequestStack(['sort' => 'id', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $view = $p->paginate($query, 1, 10, \compact(PaginatorInterface::SORT_FIELD_ALLOW_LIST));
    }

    /**
     * @test
     */
    public function shouldSortWithoutSpecificAllowList(): void
    {
        $this->populate();
        $query = $this->dm
            ->createQueryBuilder(Article::class)
            ->getQuery()
        ;

        $requestStack = $this->createRequestStack(['sort' => 'title', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $view = $p->paginate($query, 1, 10);

        $items = \array_values($view->getItems());
        $this->assertEquals('autumn', $items[0]->getTitle());

        $requestStack = $this->createRequestStack(['sort' => 'id', 'direction' => 'asc']);
        $p = $this->getPaginatorInstance($requestStack);
        $view = $p->paginate($query, 1, 10);

        $items = \array_values($view->getItems());
        $this->assertEquals('summer', $items[0]->getTitle());
    }

    private function populate(): void
    {
        $em = $this->getMockDocumentManager();
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
