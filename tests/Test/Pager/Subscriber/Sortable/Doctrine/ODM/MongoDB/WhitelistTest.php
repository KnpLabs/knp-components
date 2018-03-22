<?php

namespace Test\Pager\Subscriber\Sortable\Doctrine\ODM\MongoDB;

use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Test\Fixture\Document\Article;
use Test\Tool\BaseTestCaseMongoODM;

class WhitelistTest extends BaseTestCaseMongoODM
{
    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    function shouldWhitelistSortableFields()
    {
        $this->populate();
        $_GET['sort'] = 'title';
        $_GET['direction'] = 'asc';
        $query = $this->dm
            ->createQueryBuilder(Article::class)
            ->getQuery()
        ;

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10, [PaginatorInterface::SORT_FIELD_WHITELIST => ['title']]);

        $items = array_values($view->getItems());
        $this->assertCount(4, $items);
        $this->assertEquals('autumn', $items[0]->getTitle());
    }

    /**
     * @test
     */
    function shouldSortWithoutSpecificWhitelist()
    {
        $this->populate();
        $_GET['sort'] = 'title';
        $_GET['direction'] = 'asc';
        $query = $this->dm
            ->createQueryBuilder(Article::class)
            ->getQuery()
        ;

        $p = new Paginator;
        $view = $p->paginate($query, 1, 10);

        $items = array_values($view->getItems());
        $this->assertEquals('autumn', $items[0]->getTitle());

        $_GET['sort'] = 'id';
        $view = $p->paginate($query, 1, 10);

        $items = array_values($view->getItems());
        $this->assertEquals('summer', $items[0]->getTitle());
    }

    private function populate()
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
