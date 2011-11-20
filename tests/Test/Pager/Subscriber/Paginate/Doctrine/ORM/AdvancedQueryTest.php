<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Test\Fixture\Entity\Shop\Product;
use Test\Fixture\Entity\Shop\Tag;

class AdvancedQueryTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldBeAbleToPaginateMultiRootQuery()
    {
        $this->populate();

        $dql = <<<___SQL
    SELECT p, t FROM
      Test\Fixture\Entity\Shop\Product p,
      Test\Fixture\Entity\Shop\Tag t
___SQL;
        $q = $this->em->createQuery($dql);

        $p = new Paginator;
        $view = $p->paginate($q, 1, 10);
        $this->assertEquals(6, count($view));
    }

    /**
     * @test
     */
    function shouldBeAbleToPaginateMixedKeyArray()
    {
        $this->populate();

        $dql = <<<___SQL
        SELECT p, t, p.title FROM
          Test\Fixture\Entity\Shop\Product p
        LEFT JOIN
          p.tags t
___SQL;
        $q = $this->em->createQuery($dql);
        $p = new Paginator;
        $view = $p->paginate($q, 1, 10);
        $this->assertEquals(3, count($view));
        $items = $view->getItems();
        // and should be hydrated as array
        $this->assertTrue(isset($items[0]['title']));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            'Test\Fixture\Entity\Shop\Product',
            'Test\Fixture\Entity\Shop\Tag'
        );
    }

    private function populate()
    {
        $em = $this->getMockSqliteEntityManager();
        $cheep = new Tag;
        $cheep->setName('Cheep');

        $new = new Tag;
        $new->setName('New');

        $special = new Tag;
        $special->setName('Special');

        $starship = new Product;
        $starship->setTitle('Starship');
        $starship->setPrice(277.66);
        $starship->addTag($new);
        $starship->addTag($special);

        $cheese = new Product;
        $cheese->setTitle('Cheese');
        $cheese->setPrice(7.66);
        $cheese->addTag($cheep);

        $shoe = new Product;
        $shoe->setTitle('Shoe');
        $shoe->setPrice(2.66);
        $shoe->addTag($special);

        $em->persist($special);
        $em->persist($cheep);
        $em->persist($new);
        $em->persist($starship);
        $em->persist($cheese);
        $em->persist($shoe);
        $em->flush();
    }
}