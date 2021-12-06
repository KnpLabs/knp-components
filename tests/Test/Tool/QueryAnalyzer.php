<?php

namespace Test\Tool;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tool.Logging.DBAL
 * @subpackage QueryAnalyzer
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class QueryAnalyzer implements SQLLogger
{
    /**
     * Used database platform
     */
    protected AbstractPlatform $platform;

    /**
     * Start time of currently executed query
     *
     * @var int|float|null
     */
    private $queryStartTime = null;

    /**
     * Total execution time of all queries
     *
     * @var int|float
     */
    private $totalExecutionTime = 0;

    /**
     * List of queries executed
     */
    private array $queries = [];

    /**
     * Query execution times indexed
     * in same order as queries
     */
    private array $queryExecutionTimes = [];

    /**
     * Initialize log listener with database
     * platform, which is needed for parameter
     * conversion
     *
     * @param AbstractPlatform $platform
     */
    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    public function startQuery($sql, array $params = null, array $types = null): void
    {
        $this->queryStartTime = \microtime(true);
        $this->queries[] = $this->generateSql($sql, $params, $types);
    }

    public function stopQuery(): void
    {
        $ms = \round(\microtime(true) - $this->queryStartTime, 4) * 1000;
        $this->queryExecutionTimes[] = $ms;
        $this->totalExecutionTime += $ms;
    }

    /**
     * Clean all collected data
     */
    public function cleanUp(): QueryAnalyzer
    {
        $this->queries = [];
        $this->queryExecutionTimes = [];
        $this->totalExecutionTime = 0;

        return $this;
    }

    /**
     * Dump the statistics of executed queries
     *
     * @param boolean $dumpOnlySql
     */
    public function getOutput($dumpOnlySql = false): ?string
    {
        $output = '';
        if (!$dumpOnlySql) {
            $output .= 'Platform: ' . $this->platform->getName() . PHP_EOL;
            $output .= 'Executed queries: ' . \count($this->queries) . ', total time: ' . $this->totalExecutionTime . ' ms' . PHP_EOL;
        }
        foreach ($this->queries as $index => $sql) {
            if (!$dumpOnlySql) {
                $output .= 'Query(' . ($index+1) . ') - ' . $this->queryExecutionTimes[$index] . ' ms' . PHP_EOL;
            }
            $output .= $sql . ';' . PHP_EOL;
        }
        $output .= PHP_EOL;

        return $output;
    }

    /**
     * Index of the slowest query executed
     */
    public function getSlowestQueryIndex(): int
    {
        $index = 0;
        $slowest = 0;
        foreach ($this->queryExecutionTimes as $i => $time) {
            if ($time > $slowest) {
                $slowest = $time;
                $index = $i;
            }
        }
        return $index;
    }

    /**
     * Get total execution time of queries
     */
    public function getTotalExecutionTime(): int
    {
        return $this->totalExecutionTime;
    }

    /**
     * Get all queries
     *
     * @return array
     */
    public function getExecutedQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get number of executed queries
     */
    public function getNumExecutedQueries(): int
    {
        return \count($this->queries);
    }

    /**
     * Get all query execution times
     *
     * @return array
     */
    public function getExecutionTimes(): array
    {
        return $this->queryExecutionTimes;
    }

    /**
     * Create the SQL with mapped parameters
     *
     * @param array $params
     * @param array $types
     */
    private function generateSql(string $sql, array $params, array $types): string
    {
        if (!\count($params)) {
            return $sql;
        }
        $converted = $this->getConvertedParams($params, $types);
        if (\is_int(\key($params))) {
            $index = \key($converted);
            $sql = \preg_replace_callback('@\?@sm', static function ($match) use (&$index, $converted) {
                return \implode(' ', $converted[$index++]);
            }, $sql);
        } else {
            foreach ($converted as $key => $value) {
                $sql = \str_replace(':' . $key, $value, $sql);
            }
        }
        return $sql;
    }

    /**
     * Get the converted parameter list
     *
     * @param array $params
     * @param array $types
     *
     * @return array
     */
    private function getConvertedParams(array $params, array $types): array
    {
        $result = [];
        foreach ($params as $position => $value) {
            if (isset($types[$position])) {
                $type = $types[$position];
                if (\is_string($type)) {
                    $type = Type::getType($type);
                }
                if ($type instanceof Type) {
                    $value = $type->convertToDatabaseValue($value, $this->platform);
                }
            } else {
                if (\is_object($value) && $value instanceof \DateTime) {
                    $value = $value->format($this->platform->getDateTimeFormatString());
                } elseif (!\is_null($value)) {
                    $type = Type::getType(\gettype($value));
                    $value = $type->convertToDatabaseValue($value, $this->platform);
                }
            }
            if (\is_string($value)) {
                $value = "'{$value}'";
            } elseif (\is_null($value)) {
                $value = 'NULL';
            }
            $result[$position] = $value;
        }

        return $result;
    }
}
