<?php

namespace Javer\SphinxBundle\Logger;

use Psr\Log\LoggerInterface;

class SphinxLogger
{
    /**
     * @var array<array{sql: string, rows: int, time: float}>
     */
    protected array $queries = [];

    protected int $queriesCount = 0;

    protected int $queriesRows = 0;

    protected float $queriesTime = 0.0;

    public function __construct(
        protected ?LoggerInterface $logger = null,
    )
    {
    }

    public function logQuery(string $query, int $numRows, float $time): void
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
     * @return array<array{sql: string, rows: int, time: float}>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    public function getQueriesCount(): int
    {
        return $this->queriesCount;
    }

    public function getQueriesRows(): int
    {
        return $this->queriesRows;
    }

    public function getQueriesTime(): float
    {
        return $this->queriesTime;
    }

    public function reset(): void
    {
        $this->queries = [];
        $this->queriesCount = 0;
        $this->queriesRows = 0;
        $this->queriesTime = 0;
    }
}
