<?php

namespace Test\Pager\Subscriber\Paginate\Doctrine\ORM;

use Knp\Component\Pager\ParametersResolver;
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

        $dql = <<<SQL
        SELECT p, t
        FROM Test\Fixture\Entity\Shop\Product p
        INNER JOIN p.tags t
        GROUP BY p.id
        HAVING p.numTags = COUNT(t)
SQL;
        $query = $this->em->createQuery($dql);
        $query->setHydrationMode(Query::HYDRATE_ARRAY);
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, true);
        $this->startQueryLog();

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $view = $paginator->paginate($query, 1, 10, array('wrap-queries' => true));
        $this->assertEquals(3, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertCount(3, $view);
    }

    /**
     * @test
     */
    function shouldNotUseOutputWalkersByDefault()
    {
        $this->populate();

        $dql = <<<SQL
        SELECT p
        FROM Test\Fixture\Entity\Shop\Product p
        GROUP BY p.id
SQL;
        $query = $this->em->createQuery($dql);
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $query->setHydrationMode(Query::HYDRATE_ARRAY);
        $this->startQueryLog();

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $view = $paginator->paginate($query, 1, 10, array('wrap-queries' => false));
        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertCount(3, $view);
    }

    /**
     * @test
     */
    function shouldFetchJoinCollectionsIfNeeded()
    {
        $this->populate();

        $dql = <<<SQL
        SELECT p, t
        FROM Test\Fixture\Entity\Shop\Product p
        INNER JOIN p.tags t
        GROUP BY p.id
        HAVING p.numTags = COUNT(t)
SQL;
        $query = $this->em->createQuery($dql);
        $query->setHydrationMode(Query::HYDRATE_ARRAY);
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, true);
        $this->startQueryLog();

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $paginator = new Paginator($parametersResolver);

        $view = $paginator->paginate($query, 1, 10, array('wrap-queries' => true));
        $this->assertEquals(3, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertCount(3, $view);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Product::class,
            Tag::class
        ];
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
