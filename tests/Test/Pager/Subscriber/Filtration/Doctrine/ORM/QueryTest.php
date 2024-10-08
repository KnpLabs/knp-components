<?php

namespace Test\Pager\Subscriber\Filtration\Doctrine\ORM;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\ArgumentAccess\RequestArgumentAccess;
use Knp\Component\Pager\Event\Subscriber\Filtration\Doctrine\ORM\Query\WhereWalker;
use Knp\Component\Pager\Event\Subscriber\Filtration\FiltrationSubscriber as Filtration;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Test\Fixture\Entity\Article;
use Test\Tool\BaseTestCaseORM;

final class QueryTest extends BaseTestCaseORM
{
    #[Test]
    public function shouldHandleApcQueryCache(): void
    {
        if (!\extension_loaded('apc') || !\ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setProxyDir(__DIR__);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->getAutoGenerateProxyClasses();
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $connection = DriverManager::getConnection($conn, $config);
        $em = new \Doctrine\ORM\EntityManager($connection, $config);
        $schema = \array_map(static fn(string $class) => $em->getClassMetadata($class), $this->getUsedEntityFixtures());

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);
        $this->populate($em);

        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $requestStack = $this->createRequestStack(['filterField' => 'a.title', 'filterValue' => 'summer']);

        $p = $this->getPaginatorInstance($requestStack);
        $p->paginate($query, 1, 10);

        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $p->paginate($query, 1, 10);
    }

    #[Test]
    public function shouldFilterSimpleDoctrineQuery(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => '*er']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);

        $items = $view->getItems();

        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterSimpleDoctrineQueryWithoutWildcard(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => 'summer']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query);
        $items = $view->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterBooleanFilterValuesWithInteger(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.enabled', 'filterValue' => '1']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterBooleanFilterValues(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.enabled', 'filterValue' => 'true']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterBooleanFilterValuesWithZero(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.enabled', 'filterValue' => '0']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterBooleanFilterValuesFalse(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $this->startQueryLog();
        $requestStack = $this->createRequestStack(['filterParam' => 'a.enabled', 'filterValue' => 'false']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldNotFilterInvalidBooleanFilterValues(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.enabled', 'filterValue' => 'invalid_boolean']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterNumericFilterValues(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populateNumeric($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => '0']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('0', $items[0]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'0\' LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterNumericFilterValuesOne(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populateNumeric($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => '1']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('1', $items[0]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT count(DISTINCT a0_.id) AS sclr_0 FROM Article a0_ WHERE a0_.title LIKE \'1\'', $executed[0]);
        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'1\' LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterComplexDoctrineQuery(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => '*er']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' AND (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterComplexDoctrineQuery2(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' AND (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);

        $requestStack = $this->createRequestStack(['filterParam' => 'a.id,a.title', 'filterValue' => '*er']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' OR (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\'');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10', $executed[1]);
        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND (a0_.title <> \'\' OR (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\')) LIMIT 10', $executed[3]);
        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' LIMIT 10', $executed[5]);
    }

    #[Test]
    public function shouldFilterComplexDoctrineQuery3(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\'');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);

        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => '*er']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();
        $view = $p->paginate($query);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterSimpleDoctrineQueryWithMultipleProperties(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.id,a.title', 'filterValue' => '*er']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\' LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldFilterComplexDoctrineQueryWithMultipleProperties(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.id,a.title', 'filterValue' => '*er']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' AND (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldValidateFiltrationParameter(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => '"a.title\'', 'filterValue' => 'summer']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
    }

    #[Test]
    public function shouldValidateFiltrationParameterWithoutAlias(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'title', 'filterValue' => 'summer']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
    }

    #[Test]
    public function shouldValidateFiltrationParameterExistance(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.nonExistantField', 'filterValue' => 'summer']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
    }

    #[Test]
    public function shouldFilterByAnyAvailableAlias(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dql = <<<SQL
                    SELECT a, a.title AS test_alias
                    FROM Test\Fixture\Entity\Article a
            SQL;
        $query = $this->em->createQuery($dql);
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'test_alias', 'filterValue' => '*er']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10, [PaginatorInterface::DISTINCT => false]);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0][0]->getTitle());
        $this->assertEquals('winter', $items[1][0]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2, a0_.title AS title_3 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldNotWorkWithInitialPaginatorEventDispatcher(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);

        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => 'summer']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = $this->getPaginatorInstance($requestStack);
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertInstanceOf(SlidingPagination::class, $view);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10', $executed[1]);
    }

    #[Test]
    public function shouldNotExecuteExtraQueriesWhenCountIsZero(): void
    {
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => 'asc']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = $this->getPaginatorInstance($requestStack);
        $this->startQueryLog();
        $view = $p->paginate($query, 1, 10);
        $this->assertInstanceOf(SlidingPagination::class, $view);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
    }

    #[Test]
    public function shouldFilterWithEmptyParametersAndDefaults(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => '', 'filterValue' => 'summer']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $defaultFilterFields = 'a.title';
        $view = $p->paginate($query, 1, 10, \compact(PaginatorInterface::DEFAULT_FILTER_FIELDS));
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $defaultFilterFields = 'a.id,a.title';
        $view = $p->paginate($query, 1, 10, \compact(PaginatorInterface::DEFAULT_FILTER_FIELDS));
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $defaultFilterFields = ['a.id', 'a.title'];
        $view = $p->paginate($query, 1, 10, \compact(PaginatorInterface::DEFAULT_FILTER_FIELDS));
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10', $executed[1]);
        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10', $executed[3]);
        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10', $executed[5]);
    }

    #[Test]
    public function shouldNotFilterWithEmptyParametersAndDefaults(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $requestStack = $this->createRequestStack(['filterParam' => 'a.title', 'filterValue' => '']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);

        $requestStack = $this->createRequestStack(['filterParam' => '', 'filterValue' => 'summer']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);

        $requestStack = $this->createRequestStack(['filterParam' => '', 'filterValue' => '']);
        $accessor = new RequestArgumentAccess($requestStack);
        $p = new Paginator($dispatcher, $accessor);
        $view = $p->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10', $executed[1]);
        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10', $executed[3]);
        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10', $executed[5]);
    }

    #[Test]
    public function shouldFilterCaseInsensitiveWhenAsked(): void
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $accessor = new RequestArgumentAccess(new RequestStack());
        $p = new Paginator($dispatcher, $accessor);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $query->setHint(WhereWalker::HINT_PAGINATOR_FILTER_CASE_INSENSITIVE, true);

        $_GET['filterParam'] = '';
        $_GET['filterValue'] = 'suMmeR';
        $defaultFilterFields = 'a.title';
        $view = $p->paginate($query, 1, 10, compact('defaultFilterFields'));
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE LOWER(a0_.title) LIKE \'summer\' LIMIT 10', $executed[1]);
    }

    /**
     * @return string[]
     */
    protected function getUsedEntityFixtures(): array
    {
        return [Article::class];
    }

    private function populate(EntityManagerInterface $em): void
    {
        $summer = new Article();
        $summer->setTitle('summer');
        $summer->setEnabled(true);

        $winter = new Article();
        $winter->setTitle('winter');
        $winter->setEnabled(true);

        $autumn = new Article();
        $autumn->setTitle('autumn');
        $autumn->setEnabled(false);

        $spring = new Article();
        $spring->setTitle('spring');
        $spring->setEnabled(false);

        $em->persist($summer);
        $em->persist($winter);
        $em->persist($autumn);
        $em->persist($spring);
        $em->flush();
    }

    private function populateNumeric(EntityManagerInterface $em): void
    {
        $zero = new Article();
        $zero->setTitle('0');
        $zero->setEnabled(true);

        $one = new Article();
        $one->setTitle('1');
        $one->setEnabled(true);

        $lower = new Article();
        $lower->setTitle('123');
        $lower->setEnabled(false);

        $upper = new Article();
        $upper->setTitle('234');
        $upper->setEnabled(false);

        $em->persist($zero);
        $em->persist($one);
        $em->persist($lower);
        $em->persist($upper);
        $em->flush();
    }
}
