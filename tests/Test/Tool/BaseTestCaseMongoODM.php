<?php

namespace Test\Tool;

use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * Base test case contains common mock objects
 */
abstract class BaseTestCaseMongoODM extends BaseTestCase
{
    protected ?DocumentManager $dm = null;

    protected function setUp(): void
    {
        if (!\class_exists('MongoClient')) {
            $this->markTestSkipped('Missing Mongo extension.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->dm) {
            foreach ($this->dm->getDocumentDatabases() as $db) {
                foreach ($db->listCollections() as $collection) {
                    $collection->drop();
                }
            }
            $this->dm->getConnection()->close();
            $this->dm = null;
        }
    }

    /**
     * DocumentManager mock object together with
     * annotation mapping driver and database
     */
    protected function getMockDocumentManager(EventManager $evm = null): DocumentManager
    {
        $conn = new Connection();
        $config = $this->getMockAnnotatedConfig();

        try {
            $this->dm = DocumentManager::create($conn, $config, $evm ?: $this->getEventManager());
            $this->dm->getConnection()->connect();
        } catch (\MongoException $e) {
            $this->markTestSkipped('Doctrine MongoDB ODM failed to connect');
        }
        return $this->dm;
    }

    /**
     * DocumentManager mock object with
     * annotation mapping driver
     */
    protected function getMockMappedDocumentManager(EventManager $evm = null): DocumentManager
    {
        $conn = $this->createMock(Connection::class);
        $config = $this->getMockAnnotatedConfig();

        $this->dm = DocumentManager::create($conn, $config, $evm ?: $this->getEventManager());
        return $this->dm;
    }

    /**
     * Creates default mapping driver
     */
    protected function getMetadataDriverImplementation(): AnnotationDriver
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Build event manager
     */
    private function getEventManager(): EventManager
    {
        return new EventManager();
    }

    /**
     * Get annotation mapping configuration
     *
     * @return Configuration|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockAnnotatedConfig()
    {
        $config = $this->createMock(Configuration::class);
        $config->expects($this->once())
            ->method('getProxyDir')
            ->willReturn(__DIR__.'/../../temp');

        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->willReturn('Proxy');

        $config->expects($this->once())
            ->method('getHydratorDir')
            ->willReturn(__DIR__.'/../../temp');

        $config->expects($this->once())
            ->method('getHydratorNamespace')
            ->willReturn('Hydrator');

        $config->expects($this->any())
            ->method('getDefaultDB')
            ->willReturn('knp_pager_tests');

        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->willReturn(true);

        $config->expects($this->once())
            ->method('getAutoGenerateHydratorClasses')
            ->willReturn(true);

        $config->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->willReturn(ClassMetadataFactory::class);

        $config->expects($this->any())
            ->method('getMongoCmd')
            ->willReturn('$');

        $config
            ->expects($this->any())
            ->method('getDefaultCommitOptions')
            ->willReturn(['safe' => true])
        ;
        $mappingDriver = $this->getMetadataDriverImplementation();

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->willReturn($mappingDriver);

        return $config;
    }
}
