<?php

namespace Test\Tool;

use Psr\Log\AbstractLogger;

/**
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tool.Logging.DBAL
 * @subpackage QueryAnalyzer
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class QueryAnalyzer extends AbstractLogger
{
    /** @var array<int, array{message: string, context: mixed[]}> */
    public array $queries = [];

    /**
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->queries[] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    public function reset(): void
    {
        $this->queries = [];
    }

    /**
     * Total execution time of all queries
     *
     * @var int|float
     */
    private $totalExecutionTime = 0;

    /**
     * Query execution times indexed
     * in same order as queries
     */
    private array $queryExecutionTimes = [];

    /**
     * Dump the statistics of executed queries
     */
    public function getOutput(bool $dumpOnlySql = false): ?string
    {
        $output = '';
        if (!$dumpOnlySql) {
            $output .= 'Platform: (unknown) ' . PHP_EOL;
            $output .= 'Executed queries: ' . \count($this->queries) . ', total time: ' . $this->totalExecutionTime . ' ms' . PHP_EOL;
        }
        foreach ($this->queries as $index => $sql) {
            if (!$dumpOnlySql) {
                $output .= 'Query(' . ($index+1) . ') - ' . $this->queryExecutionTimes[$index] . ' ms' . PHP_EOL;
            }
            $output .= $sql['message'] . ';' . PHP_EOL;
        }
        $output .= PHP_EOL;

        return $output;
    }

    public function getExecutedQueries(): array
    {
        return $this->queries;
    }

    public function getNumExecutedQueries(): int
    {
        return \count($this->queries);
    }
}
