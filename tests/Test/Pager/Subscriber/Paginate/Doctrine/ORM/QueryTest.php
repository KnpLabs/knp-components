<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ORM;

use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Test\Fixture\Entity\Shop\Product;
use Test\Fixture\Entity\Shop\Tag;
use Doctrine\ORM\Query;

class QueryTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    function shouldUseOutputWalkersIfAskedTo()
    {
        $this->populate();

        $dql = <<<___SQL
        SELECT p, t
        FROM Test\Fixture\Entity\Shop\Product p
        INNER JOIN p.tags t
        GROUP BY p.id
        HAVING p.numTags = COUNT(t)
___SQL;
        $q = $this->em->createQuery($dql);
        $q->setHydrationMode(Query::HYDRATE_ARRAY);
        $q->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, true);
        $this->startQueryLog();
        $p = new Paginator;
        $view = $p->paginate($q, 1, 10, array('wrap-queries' => true));
        $this->assertEquals(3, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals(3, count($view));
    }

    /**
     * @test
     */
    function shouldNotUseOutputWalkersByDefault()
    {
        $this->populate();

        $dql = <<<___SQL
        SELECT p
        FROM Test\Fixture\Entity\Shop\Product p
        GROUP BY p.id
___SQL;
        $q = $this->em->createQuery($dql);
        $q->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $q->setHydrationMode(Query::HYDRATE_ARRAY);
        $this->startQueryLog();
        $p = new Paginator;
        $view = $p->paginate($q, 1, 10, array('wrap-queries' => false));
        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals(3, count($view));
    }

    /**
     * @test
     */
    function shouldFetchJoinCollectionsIfNeeded()
    {
        $this->populate();

        $dql = <<<___SQL
        SELECT p, t
        FROM Test\Fixture\Entity\Shop\Product p
        INNER JOIN p.tags t
        GROUP BY p.id
        HAVING p.numTags = COUNT(t)
___SQL;
        $q = $this->em->createQuery($dql);
        $q->setHydrationMode(Query::HYDRATE_ARRAY);
        $q->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, true);
        $this->startQueryLog();
        $p = new Paginator;
        $view = $p->paginate($q, 1, 10, array('wrap-queries' => true));
        $this->assertEquals(3, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals(3, count($view));
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
