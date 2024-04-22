<?php

namespace Test\Tool;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo
 * @subpackage BaseTestCase
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseTestCaseORM extends BaseTestCase
{
    protected ?EntityManager $em = null;

    protected ?QueryAnalyzer $queryAnalyzer = null;

    protected function setUp(): void
    {
        $this->queryAnalyzer = new QueryAnalyzer();
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     */
    protected function getMockSqliteEntityManager(?EventManager $evm = null): EntityManager
    {
        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = $this->getMockAnnotatedConfig();
        $connection = DriverManager::getConnection($conn, $config);

        $em = new EntityManager($connection, $config, $evm ?: $this->getEventManager());

        $schema = \array_map(static fn (string $class): ClassMetadata => $em->getClassMetadata($class), $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and custom connection
     */
    protected function getMockCustomEntityManager(array $conn, ?EventManager $evm = null): EntityManager
    {
        $config = $this->getMockAnnotatedConfig();
        $connection = DriverManager::getConnection($conn, $config);
        $em = new EntityManager($connection, $config, $evm ?: $this->getEventManager());

        $schema = \array_map(static function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * EntityManager mock object with
     * annotation mapping driver
     */
    protected function getMockMappedEntityManager(?EventManager $evm = null): EntityManager
    {
        $driver = $this->createMock(Driver::class);
        $driver->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($this->createMock(MySqlPlatform::class));

        $connection = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([[], $driver])
            ->getMock();

        $connection->expects($this->once())
            ->method('getEventManager')
            ->willReturn($evm ?: $this->getEventManager());

        $config = $this->getMockAnnotatedConfig();
        $this->em = new EntityManager($connection, $config);

        return $this->em;
    }

    /**
     * Starts query statistic log
     *
     * @throws \RuntimeException
     */
    protected function startQueryLog(): void
    {
        if (null === $this->em) {
            throw new \RuntimeException('EntityManager and database platform must be initialized');
        }
        
        $this->queryAnalyzer->enable();
    }

    /**
     * Stops query statistic log and outputs
     * the data to screen or file
     *
     * @throws \RuntimeException
     */
    protected function stopQueryLog(bool $dumpOnlySql = false, bool $writeToLog = false): void
    {
        if (null !== $this->queryAnalyzer) {
            \ob_start();
            $this->queryAnalyzer->getOutput($dumpOnlySql);
            $output = \ob_get_clean();

            if (!$writeToLog) {
                echo $output;

                return;
            }

            $fileName = __DIR__.'/../../temp/query_debug_'.\time().'.log';
            if (($file = \fopen($fileName, 'wb+')) === false) {
                throw new \RuntimeException('Unable to write to the log file');
            }

            \fwrite($file, $output);
            \fclose($file);
        }
    }

    /**
     * Creates default mapping driver
     */
    protected function getMetadataDriverImplementation(): MappingDriver
    {
        if (class_exists(AnnotationDriver::class)) {
            return new AnnotationDriver($_ENV['annotation_reader']);
        }

        return new AttributeDriver([]);
    }

    /**
     * Get a list of used fixture classes
     *
     * @return array<int, class-string>
     */
    abstract protected function getUsedEntityFixtures(): array;

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
     * @return Configuration&\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockAnnotatedConfig()
    {
        $config = $this->createMock(Configuration::class);
        $config
            ->expects($this->once())
            ->method('getProxyDir')
            ->willReturn(__DIR__.'/../../temp')
        ;

        $config
            ->expects($this->once())
            ->method('getProxyNamespace')
            ->willReturn('Proxy')
        ;

        $config
            ->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->willReturn(1)
        ;

        $metadata = $this->createMock(ClassMetadata::class);
        $factory = $this->createMock(ClassMetadataFactory::class);

        $factory
            ->method('getMetadataFor')
            ->willReturn($metadata)
        ;

        $config
            ->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->willReturn(ClassMetadataFactory::class)
        ;

        $mappingDriver = $this->getMetadataDriverImplementation();

        $config
            ->method('getMetadataDriverImpl')
            ->willReturn($mappingDriver)
        ;

        $config
            ->method('getDefaultRepositoryClassName')
            ->willReturn(EntityRepository::class)
        ;

        $config
            ->method('getQuoteStrategy')
            ->willReturn(new DefaultQuoteStrategy())
        ;

        $config
            ->method('getNamingStrategy')
            ->willReturn(new DefaultNamingStrategy())
        ;

        $config
            ->method('getCustomHydrationMode')
            ->willReturn('Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\AsIsHydrator')
        ;

        $config
            ->method('getDefaultQueryHints')
            ->willReturn([])
        ;

        $config
            ->method('getMiddlewares')
            ->willReturn([new Middleware($this->queryAnalyzer)]);

        return $config;
    }
}
