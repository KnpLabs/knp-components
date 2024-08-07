<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ODM\PHPCR;

use Knp\Component\Pager\ArgumentAccess\ArgumentAccessInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ODM\PHPCR\QuerySubscriber;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Test\Fixture\Document\PHPCR\Article;
use Test\Mock\PaginationSubscriber;
use Test\Tool\BaseTestCasePHPCRODM;

final class QuerySubscriberTest extends BaseTestCasePHPCRODM
{
    #[Test]
    public function shouldPaginateSimpleDoctrinePHPCRQuery(): void
    {
        $this->populate();

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new QuerySubscriber());
        $dispatcher->addSubscriber(new PaginationSubscriber()); // pagination view
        $accessor = $this->createMock(ArgumentAccessInterface::class);
        $p = new Paginator($dispatcher, $accessor);

        $query = $this->dm->createQueryBuilder()->fromDocument(Article::class, 'a')->getQuery();

        $pagination = $p->paginate($query, 1, 2);

        $this->assertInstanceOf(SlidingPagination::class, $pagination);
        $this->assertEquals(1, $pagination->getCurrentPageNumber());
        $this->assertEquals(2, $pagination->getItemNumberPerPage());
        $this->assertEquals(4, $pagination->getTotalItemCount());

        $items = $pagination->getItems();

        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items->first()->getTitle());
        $this->assertEquals('winter', $items->last()->getTitle());
    }

    #[Test]
    public function shouldSupportPaginateStrategySubscriber(): void
    {
        $this->getMockDocumentManager();
        $query = $this->dm->createQueryBuilder()->fromDocument(Article::class, 'a')->getQuery();

        $p = $this->getPaginatorInstance();
        $pagination = $p->paginate($query, 1, 10);
        $this->assertInstanceOf(SlidingPagination::class, $pagination);
    }

    private function populate(): void
    {
        $dm = $this->getMockDocumentManager();

        $root = $dm->find(null, '/');

        $summer = new Article();
        $summer->setTitle('summer');
        $summer->setParent($root);

        $winter = new Article();
        $winter->setTitle('winter');
        $winter->setParent($root);

        $autumn = new Article();
        $autumn->setTitle('autumn');
        $autumn->setParent($root);

        $spring = new Article();
        $spring->setTitle('spring');
        $spring->setParent($root);

        $dm->persist($summer);
        $dm->persist($winter);
        $dm->persist($autumn);
        $dm->persist($spring);
        $dm->flush();
    }
}
