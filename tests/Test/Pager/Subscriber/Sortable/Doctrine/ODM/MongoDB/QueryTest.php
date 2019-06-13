<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Test\Tool\BaseTestCaseMongoODM;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\Doctrine\ODM\MongoDB\QuerySubscriber as Sortable;
use Test\Fixture\Document\Article;

class QueryTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     */
    public function shouldSortSimpleDoctrineQuery(): void
    {
        $this->populate();

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new PaginationSubscriber);
        $dispatcher->addSubscriber(new Sortable);
        $requestStack = $this->createRequestStack(['sort' => 'title', 'direction' => 'asc']);
        $p = new Paginator($dispatcher, $requestStack);

        $qb = $this->dm->createQueryBuilder(Article::class);
        $query = $qb->getQuery();
        $view = $p->paginate($query, 1, 10);

        $items = array_values($view->getItems());
        $this->assertCount(4, $items);
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());
        $this->assertEquals('summer', $items[2]->getTitle());
        $this->assertEquals('winter', $items[3]->getTitle());

        $requestStack = $this->createRequestStack(['sort' => 'title', 'direction' => 'desc']);
        $p = new Paginator($dispatcher, $requestStack);
        $view = $p->paginate($query, 1, 10);
        $items = array_values($view->getItems());
        $this->assertCount(4, $items);
        $this->assertEquals('winter', $items[0]->getTitle());
        $this->assertEquals('summer', $items[1]->getTitle());
        $this->assertEquals('spring', $items[2]->getTitle());
        $this->assertEquals('autumn', $items[3]->getTitle());
    }

    /**
     * @test
     */
    public function shouldSortOnAnyField(): void
    {
        $query = $this
            ->getMockDocumentManager()
            ->createQueryBuilder(Article::class)
            ->getQuery()
        ;

        $requestStack = $this->createRequestStack(['sort' => '"title\'', 'direction' => 'asc']);
        $p = new Paginator(null, $requestStack);
        $view = $p->paginate($query, 1, 10);
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
