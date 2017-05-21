<?php

namespace Javer\SphinxBundle\Sphinx;

use Javer\SphinxBundle\Logger\SphinxLogger;
use PDO;

/**
 * Class Manager
 *
 * @package Javer\SphinxBundle\Sphinx
 */
class Manager
{
    /**
     * @var SphinxLogger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var PDO
     */
    protected $connection;

    /**
     * Connection constructor.
     *
     * @param SphinxLogger $logger
     * @param string       $host
     * @param string       $port
     */
    public function __construct(SphinxLogger $logger, string $host, string $port)
    {
        $this->logger = $logger;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Returns an established connection to Sphinx server.
     *
     * @return PDO
     */
    protected function getConnection(): PDO
    {
        if (is_null($this->connection)) {
            $this->connection = new PDO(sprintf('mysql:host=%s;port=%d', $this->host, $this->port));

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->connection;
    }

    /**
     * Creates a new query.
     *
     * @return Query
     */
    public function createQuery(): Query
    {
        return new Query($this->getConnection(), $this->logger);
    }
}
