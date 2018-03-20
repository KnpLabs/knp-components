<?php

namespace Test\Pager\Subscriber\Filtration\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber;
use Knp\Component\Pager\ParametersResolver;
use Test\Tool\BaseTestCaseORM;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Filtration\FiltrationSubscriber as Filtration;
use Test\Fixture\Entity\Article;
use Knp\Component\Pager\PaginatorInterface;

class QueryTest extends BaseTestCaseORM
{
    /**
     * @test
     */
    public function shouldHandleApcQueryCache()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setProxyDir(__DIR__);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $config->getAutoGenerateProxyClasses(false);
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $connection = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $em = \Doctrine\ORM\EntityManager::create($connection, $config);
        $schema = array_map(function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
        $this->populate($em);

        $_GET['filterField'] = 'a.title';
        $_GET['filterValue'] = 'summer';
        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');

        $p = new Paginator();
        $view = $p->paginate($query, 1, 10);

        $query = $em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $view = $p->paginate($query, 1, 10);
    }

    /**
     * @test
     */
    public function shouldFilterSimpleDoctrineQuery()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.title'],
                ['filterValue', null, '*er'],
            ]));

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);

        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.title'],
                ['filterValue', null, 'summer'],
            ]));

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $this->assertEquals(4, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterBooleanFilterValues()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.enabled'],
                ['filterValue', null, '1'],
            ]));
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.enabled'],
                ['filterValue', null, 'true'],
            ]));
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.enabled'],
                ['filterValue', null, '0'],
            ]));
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.enabled'],
                ['filterValue', null, 'false'],
            ]));
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('autumn', $items[0]->getTitle());
        $this->assertEquals('spring', $items[1]->getTitle());

        $this->assertEquals(8, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[7]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 1 LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.enabled = 0 LIMIT 10 OFFSET 0', $executed[7]);
        }
    }

    /**
     * @test
     */
    public function shouldNotFilterInvalidBooleanFilterValues()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.enabled'],
                ['filterValue', null, 'invalid_boolean'],
            ]));
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterNumericFilterValues()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populateNumeric($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $this->startQueryLog();

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.title'],
                ['filterValue', null, '0'],
            ]));
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('0', $items[0]->getTitle());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.title'],
                ['filterValue', null, '1'],
            ]));
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('1', $items[0]->getTitle());

        $this->assertEquals(4, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title = 0 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title = 1 LIMIT 10 OFFSET 0', $executed[3]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title = 0 LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title = 1 LIMIT 10 OFFSET 0', $executed[3]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterComplexDoctrineQuery()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());


        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.title'],
                ['filterValue', null, '*er'],
            ]));

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' AND (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.id,a.title'],
                ['filterValue', null, '*er'],
            ]));

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' OR (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\'');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.title'],
                ['filterValue', null, '*er'],
            ]));

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND (a0_.title <> \'\' OR (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\')) LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[7]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[9]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND (a0_.title <> \'\' OR (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\')) LIMIT 10 OFFSET 0', $executed[5]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[7]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'%er\' AND a0_.title <> \'\' LIMIT 10 OFFSET 0', $executed[9]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterSimpleDoctrineQueryWithMultipleProperties()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.id,a.title'],
                ['filterValue', null, '*er'],
            ]));

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0]->getTitle());
        $this->assertEquals('winter', $items[1]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     */
    public function shouldFilterComplexDoctrineQueryWithMultipleProperties()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.id,a.title'],
                ['filterValue', null, '*er'],
            ]));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a WHERE a.title <> \'\' AND (a.title LIKE \'summer\' OR a.title LIKE \'spring\')');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();

        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE (a0_.id LIKE \'%er\' OR a0_.title LIKE \'%er\') AND a0_.title <> \'\' AND (a0_.title LIKE \'summer\' OR a0_.title LIKE \'spring\') LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function shouldValidateFiltrationParameter()
    {
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, '"a.title\''],
                ['filterValue', null, 'summer'],
            ]));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $paginator = new Paginator($parametersResolver, $dispatcher);
        $view = $paginator->paginate($query, 1, 10);
        $view->getItems();
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function shouldValidateFiltrationParameterWithoutAlias()
    {
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;


        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'title'],
                ['filterValue', null, 'summer'],
            ]));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $paginator = new Paginator($parametersResolver, $dispatcher);
        $view = $paginator->paginate($query, 1, 10);
        $view->getItems();
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function shouldValidateFiltrationParameterExistance()
    {
        $_GET['filterParam'] = 'a.nonExistantField';
        $_GET['filterValue'] = 'summer';
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver
            ->method('get')
            ->will($this->returnValueMap([
                ['filterParam', null, 'a.nonExistantField'],
                ['filterValue', null, 'summer'],
            ]));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $paginator = new Paginator($parametersResolver, $dispatcher);
        $view = $paginator->paginate($query, 1, 10);
        $view->getItems();
    }

    /**
     * @test
     */
    public function shouldFilterByAnyAvailableAlias()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $dql = <<<SQL
        SELECT a, a.title AS test_alias
        FROM Test\Fixture\Entity\Article a
SQL;
        $query = $this->em->createQuery($dql);
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver->method('get')->will($this->returnValueMap([
            ['filterParam', null, 'test_alias'],
            ['filterValue', null, '*er'],
        ]));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());

        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();
        $view = $paginator->paginate($query, 1, 10, [PaginatorInterface::DISTINCT => false]);
        $items = $view->getItems();
        $this->assertCount(2, $items);
        $this->assertEquals('summer', $items[0][0]->getTitle());
        $this->assertEquals('winter', $items[1][0]->getTitle());

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2, a0_.title AS title3 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2, a0_.title AS title_3 FROM Article a0_ WHERE a0_.title LIKE \'%er\' LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     */
    public function shouldNotWorkWithInitialPaginatorEventDispatcher()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver->method('get')->will($this->returnValueMap([
            ['filterParam', null, 'a.title'],
            ['filterValue', null, 'summer'],
        ]));

        $paginator = new Paginator($parametersResolver);

        $this->startQueryLog();
        $view = $paginator->paginate($query, 1, 10);
        $this->assertInstanceOf(SlidingPagination::class, $view);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
        }
    }

    /**
     * @test
     */
    public function shouldNotExecuteExtraQueriesWhenCountIsZero()
    {
        $query = $this
            ->getMockSqliteEntityManager()
            ->createQuery('SELECT a FROM Test\Fixture\Entity\Article a')
        ;

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver->method('get')->will($this->returnValueMap([
            ['filterParam', null, 'a.title'],
            ['filterValue', null, 'asc'],
        ]));

        $paginator = new Paginator($parametersResolver);
        $this->startQueryLog();
        $view = $paginator->paginate($query, 1, 10);
        $this->assertInstanceOf(SlidingPagination::class, $view);

        $this->assertEquals(2, $this->queryAnalyzer->getNumExecutedQueries());
    }

    /**
     * @test
     */
    public function shouldFilterWithEmptyParametersAndDefaults()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $parametersResolver->method('get')->will($this->returnValueMap([
            ['filterParam', 'a.title', 'a.title'],
            ['filterParam', 'a.id,a.title', 'a.id,a.title'],
            ['filterValue', null, 'summer'],
        ]));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);

        $defaultFilterFields = 'a.title';
        $view = $paginator->paginate($query, 1, 10, compact('defaultFilterFields'));
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $defaultFilterFields = 'a.id,a.title';
        $view = $paginator->paginate($query, 1, 10, compact('defaultFilterFields'));
        $items = $view->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals('summer', $items[0]->getTitle());

        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ WHERE a0_.id LIKE \'summer\' OR a0_.title LIKE \'summer\' LIMIT 10 OFFSET 0', $executed[3]);
        }
    }

    /**
     * @test
     */
    public function shouldNotFilterWithEmptyParametersAndDefaults()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->populate($em);

        $parametersResolver = $this->createMock(ParametersResolver::class);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new PaginationSubscriber());
        $dispatcher->addSubscriber(new Filtration());
        $paginator = new Paginator($parametersResolver, $dispatcher);

        $parametersResolver->method('get')->will($this->returnValueMap([
            ['filterParam', null, 'a.title'],
            ['filterValue', null, ''],
        ]));

        $this->startQueryLog();
        $query = $this->em->createQuery('SELECT a FROM Test\Fixture\Entity\Article a');
        $query->setHint(QuerySubscriber::HINT_FETCH_JOIN_COLLECTION, false);
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);

        $parametersResolver->method('get')->will($this->returnValueMap([
            ['filterParam', null, ''],
            ['filterValue', null, 'summer'],
        ]));
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);

        $parametersResolver->method('get')->will($this->returnValueMap([
            ['filterParam', null, ''],
            ['filterValue', null, ''],
        ]));
        $view = $paginator->paginate($query, 1, 10);
        $items = $view->getItems();
        $this->assertCount(4, $items);
        $executed = $this->queryAnalyzer->getExecutedQueries();

        // Different aliases separators according to Doctrine version
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.5', '<')) {
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id0, a0_.title AS title1, a0_.enabled AS enabled2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[5]);
        } else {
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[1]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[3]);
            $this->assertEquals('SELECT a0_.id AS id_0, a0_.title AS title_1, a0_.enabled AS enabled_2 FROM Article a0_ LIMIT 10 OFFSET 0', $executed[5]);
        }
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Article::class];
    }

    private function populate(EntityManagerInterface $em)
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

    private function populateNumeric(EntityManagerInterface $em)
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
