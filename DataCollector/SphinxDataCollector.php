<?php

namespace Javer\SphinxBundle\DataCollector;

use Javer\SphinxBundle\Logger\SphinxLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

class SphinxDataCollector extends DataCollector
{
    public function __construct(
        protected SphinxLogger $logger,
    )
    {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'queries' => $this->logger->getQueries(),
            'queriesCount' => $this->logger->getQueriesCount(),
            'queriesRows' => $this->logger->getQueriesRows(),
            'queriesTime' => $this->logger->getQueriesTime(),
        ];
    }

    /**
     * Returns an array of queries.
     *
     * @return array<array{sql: string, rows: int, time: float}>
     */
    public function getQueries(): array
    {
        return $this->data['queries'];
    }

    public function getQueriesCount(): int
    {
        return $this->data['queriesCount'];
    }

    public function getQueriesRows(): int
    {
        return $this->data['queriesRows'];
    }

    public function getQueriesTime(): float
    {
        return $this->data['queriesTime'];
    }

    public function reset(): void
    {
        $this->data = [];

        $this->logger->reset();
    }

    public function getName(): string
    {
        return 'sphinx';
    }
}
