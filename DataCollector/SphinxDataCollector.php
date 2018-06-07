<?php

namespace Javer\SphinxBundle\DataCollector;

use Javer\SphinxBundle\Logger\SphinxLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class SphinxDataCollector
 *
 * @package Javer\SphinxBundle\DataCollector
 */
class SphinxDataCollector extends DataCollector
{
    /**
     * @var SphinxLogger
     */
    protected $logger;

    /**
     * SphinxDataCollector constructor.
     *
     * @param SphinxLogger $logger
     */
    public function __construct(SphinxLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
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
     * @return array
     */
    public function getQueries(): array
    {
        return $this->data['queries'];
    }

    /**
     * Returns queries count.
     *
     * @return integer
     */
    public function getQueriesCount(): int
    {
        return $this->data['queriesCount'];
    }

    /**
     * Returns queries rows.
     *
     * @return integer
     */
    public function getQueriesRows(): int
    {
        return $this->data['queriesRows'];
    }

    /**
     * Returns queries time.
     *
     * @return float
     */
    public function getQueriesTime(): float
    {
        return $this->data['queriesTime'];
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];

        $this->logger->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sphinx';
    }
}
