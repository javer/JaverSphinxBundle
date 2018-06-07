<?php

namespace Javer\SphinxBundle\Logger;

use Psr\Log\LoggerInterface;

/**
 * Class SphinxLogger
 *
 * @package Javer\SphinxBundle\Logger
 */
class SphinxLogger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $queries = [];

    /**
     * @var integer
     */
    protected $queriesCount = 0;

    /**
     * @var integer
     */
    protected $queriesRows = 0;

    /**
     * @var float
     */
    protected $queriesTime = 0;

    /**
     * SphinxLogger constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Logs a query.
     *
     * @param string  $query
     * @param integer $numRows
     * @param float   $time
     */
    public function logQuery(string $query, int $numRows, float $time)
    {
        $this->queries[] = [
            'sql' => $query,
            'rows' => $numRows,
            'time' => $time,
        ];

        $this->queriesCount++;
        $this->queriesRows += $numRows;
        $this->queriesTime += $time;

        if ($this->logger) {
            $this->logger->debug($query);
        }
    }

    /**
     * Returns queries.
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Returns queries count.
     *
     * @return integer
     */
    public function getQueriesCount(): int
    {
        return $this->queriesCount;
    }

    /**
     * Returns queries rows.
     *
     * @return integer
     */
    public function getQueriesRows(): int
    {
        return $this->queriesRows;
    }

    /**
     * Returns queries time.
     *
     * @return float
     */
    public function getQueriesTime(): float
    {
        return $this->queriesTime;
    }

    /**
     * Resets internal state.
     */
    public function reset()
    {
        $this->queries = [];
        $this->queriesCount = 0;
        $this->queriesRows = 0;
        $this->queriesTime = 0;
    }
}
