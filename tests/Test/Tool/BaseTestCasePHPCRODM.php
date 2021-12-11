<?php

namespace Test\Tool;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\Query\Query;
use Jackalope\RepositoryFactoryDoctrineDBAL;
use Jackalope\Transport\DoctrineDBAL\RepositorySchema;

/**
 * Base test case contains common mock objects
 */
abstract class BaseTestCasePHPCRODM extends BaseTestCase
{
    protected ?DocumentManager $dm = null;

    protected function setUp(): void
    {
        if (!\class_exists(Query::class)) {
            $this->markTestSkipped('Doctrine PHPCR-ODM is not available');
        }
    }

    protected function tearDown(): void
    {
        if ($this->dm) {
            $this->dm = null;
        }
    }

    protected function getMockDocumentManager(EventManager $evm = null): DocumentManager
    {
        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        $this->dm = DocumentManager::create($this->getSession(), $config, $evm ?: $this->getEventManager());

        return $this->dm;
    }

    protected function getMetadataDriverImplementation(): AnnotationDriver
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    private function getSession()
    {
        try {
            $connection = DriverManager::getConnection([
                'driver' => 'pdo_sqlite',
                'path' => ':memory:',
            ]);
            $factory = new RepositoryFactoryDoctrineDBAL();
            $repository = $factory->getRepository([
                'jackalope.doctrine_dbal_connection' => $connection,
            ]);

            $schema = new RepositorySchema(['disable_fks' => true], $connection);
            $schema->reset();

            $session = $repository->login(new \PHPCR\SimpleCredentials('', ''));

            $cnd = <<<CND
                <phpcr='http://www.doctrine-project.org/projects/phpcr_odm'>
                [phpcr:managed]
                mixin
                - phpcr:class (STRING)
                - phpcr:classparents (STRING) multiple
                CND;

            $session->getWorkspace()->getNodeTypeManager()->registerNodeTypesCnd($cnd, true);

            return $session;
        } catch (DBALException $exception) {
            self::markTestIncomplete($exception->getMessage());
        }
    }

    private function getEventManager(): EventManager
    {
        return new EventManager();
    }
}
