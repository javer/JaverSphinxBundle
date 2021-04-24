<?php

namespace Javer\SphinxBundle\Sphinx;

use Javer\SphinxBundle\Logger\SphinxLogger;
use PDO;

class Manager
{
    protected ?PDO $connection = null;

    public function __construct(
        protected SphinxLogger $logger,
        protected string $host,
        protected string $port,
    )
    {
    }

    protected function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = new PDO(sprintf('mysql:host=%s;port=%d', $this->host, $this->port));

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $this->connection;
    }

    public function createQuery(): Query
    {
        return new Query($this->getConnection(), $this->logger);
    }

    public function closeConnection(): void
    {
        $this->connection = null;
    }
}
