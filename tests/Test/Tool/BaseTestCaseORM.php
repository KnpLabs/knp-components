<?php

namespace Test\Tool;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Tools\SchemaTool;

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
abstract class BaseTestCaseORM extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var QueryAnalyzer
     */
    protected $queryAnalyzer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {

    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @param EventManager $evm
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager(EventManager $evm = null)
    {
        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $config = $this->getMockAnnotatedConfig();
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(function($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array)$this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
        return $this->em = $em;
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and custom
     * connection
     *
     * @param array $conn
     * @param EventManager $evm
     * @return EntityManager
     */
    protected function getMockCustomEntityManager(array $conn, EventManager $evm = null)
    {
        $config = $this->getMockAnnotatedConfig();
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(function($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array)$this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
        return $this->em = $em;
    }

    /**
     * EntityManager mock object with
     * annotation mapping driver
     *
     * @param EventManager $evm
     * @return EntityManager
     */
    protected function getMockMappedEntityManager(EventManager $evm = null)
    {
        $driver = $this->getMock('Doctrine\DBAL\Driver');
        $driver->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->getMock('Doctrine\DBAL\Platforms\MySqlPlatform')));

        $conn = $this->getMock('Doctrine\DBAL\Connection', array(), array(array(), $driver));
        $conn->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnValue($evm ?: $this->getEventManager()));

        $config = $this->getMockAnnotatedConfig();
        $this->em = EntityManager::create($conn, $config);
        return $this->em;
    }

    /**
     * Starts query statistic log
     *
     * @throws \RuntimeException
     */
    protected function startQueryLog()
    {
        if (!$this->em || !$this->em->getConnection()->getDatabasePlatform()) {
            throw new \RuntimeException('EntityManager and database platform must be initialized');
        }
        $this->queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getSQLLogger')
            ->will($this->returnValue($this->queryAnalyzer));
    }

    /**
     * Stops query statistic log and outputs
     * the data to screen or file
     *
     * @param boolean $dumpOnlySql
     * @param boolean $writeToLog
     * @throws \RuntimeException
     */
    protected function stopQueryLog($dumpOnlySql = false, $writeToLog = false)
    {
        if ($this->queryAnalyzer) {
            $output = $this->queryAnalyzer->getOutput($dumpOnlySql);
            if ($writeToLog) {
                $fileName = __DIR__.'/../../temp/query_debug_'.time().'.log';
                if (($file = fopen($fileName, 'w+')) !== false) {
                    fwrite($file, $output);
                    fclose($file);
                } else {
                    throw new \RuntimeException('Unable to write to the log file');
                }
            } else {
                echo $output;
            }
        }
    }

    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getMetadataDriverImplementation()
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Get a list of used fixture classes
     *
     * @return array
     */
    abstract protected function getUsedEntityFixtures();

    /**
     * Build event manager
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        $evm = new EventManager;
        return $evm;
    }

    /**
     * Get annotation mapping configuration
     *
     * @return Doctrine\ORM\Configuration
     */
    private function getMockAnnotatedConfig()
    {
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config
            ->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(__DIR__.'/../../temp'))
        ;

        $config
            ->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'))
        ;

        $config
            ->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true))
        ;

        $config
            ->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'))
        ;

        $mappingDriver = $this->getMetadataDriverImplementation();

        $config
            ->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver))
        ;

        $config
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'))
        ;

        $config
            ->expects($this->any())
            ->method('getQuoteStrategy')
            ->will($this->returnValue(new DefaultQuoteStrategy()))
        ;

        $config
            ->expects($this->any())
            ->method('getNamingStrategy')
            ->will($this->returnValue(new DefaultNamingStrategy()))
        ;

        $config
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->will($this->returnValue('Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\Query\AsIsHydrator'))
        ;

        return $config;
    }
}
