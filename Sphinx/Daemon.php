<?php

namespace Javer\SphinxBundle\Sphinx;

use RuntimeException;

class Daemon
{
    public function __construct(
        protected string $searchdPath,
        protected string $configPath,
        protected string $pidPath,
        protected int $startTimeout,
        protected int $stopTimeout,
    )
    {
    }

    /**
     * Start daemon.
     *
     * @return Daemon
     *
     * @throws RuntimeException
     */
    public function start(): self
    {
        $command = sprintf(
            '%s -c %s',
            escapeshellcmd($this->getSearchdPath()),
            escapeshellarg($this->getConfigPath())
        );

        exec($command, $output, $returnStatus);

        if ($returnStatus !== 0) {
            throw new RuntimeException(sprintf('Cannot start sphinx daemon, return code: %d', $returnStatus));
        }

        sleep($this->getStartTimeout());

        return $this;
    }

    public function stop(): self
    {
        $pidPath = $this->getPidPath();

        if ($pidPath === '' || !file_exists($pidPath) || ($pid = (int) file_get_contents($pidPath)) === 0) {
            return $this;
        }

        posix_kill($pid, SIGTERM);

        $endTime = microtime(true) + $this->getStopTimeout();

        while ($endTime > microtime(true) && posix_getpgid($pid)) {
            usleep(100000);
        }

        return $this;
    }

    public function getSearchdPath(): string
    {
        return $this->searchdPath;
    }

    public function setSearchdPath(string $searchdPath): self
    {
        $this->searchdPath = $searchdPath;

        return $this;
    }

    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    public function setConfigPath(string $configPath): self
    {
        $this->configPath = $configPath;

        return $this;
    }

    public function getPidPath(): string
    {
        return $this->pidPath;
    }

    public function setPidPath(string $pidPath): self
    {
        $this->pidPath = $pidPath;

        return $this;
    }

    public function getStartTimeout(): int
    {
        return $this->startTimeout;
    }

    public function setStartTimeout(int $startTimeout): self
    {
        $this->startTimeout = $startTimeout;

        return $this;
    }

    public function getStopTimeout(): int
    {
        return $this->stopTimeout;
    }

    public function setStopTimeout(int $stopTimeout): self
    {
        $this->stopTimeout = $stopTimeout;

        return $this;
    }
}
