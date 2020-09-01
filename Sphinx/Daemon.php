<?php

namespace Javer\SphinxBundle\Sphinx;

use RuntimeException;

/**
 * Class Daemon
 *
 * @package Javer\SphinxBundle\Sphinx
 */
class Daemon
{
    /**
     * @var string
     */
    protected $searchdPath;

    /**
     * @var string
     */
    protected $configPath;

    /**
     * @var string
     */
    protected $pidPath;

    /**
     * @var integer
     */
    protected $startTimeout;

    /**
     * @var integer
     */
    protected $stopTimeout;

    /**
     * Daemon constructor.
     *
     * @param string  $searchdPath
     * @param string  $configPath
     * @param string  $pidPath
     * @param integer $startTimeout
     * @param integer $stopTimeout
     */
    public function __construct(
        string $searchdPath,
        string $configPath,
        string $pidPath,
        int $startTimeout,
        int $stopTimeout
    )
    {
        $this->searchdPath = $searchdPath;
        $this->configPath = $configPath;
        $this->pidPath = $pidPath;
        $this->startTimeout = $startTimeout;
        $this->stopTimeout = $stopTimeout;
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

    /**
     * Stop daemon.
     *
     * @return Daemon
     */
    public function stop(): self
    {
        $pidPath = $this->getPidPath();

        if ($pidPath === null || !file_exists($pidPath) || ($pid = (int) file_get_contents($pidPath)) === 0) {
            return $this;
        }

        posix_kill($pid, SIGTERM);

        $endTime = microtime(true) + $this->getStopTimeout();

        while ($endTime > microtime(true) && posix_getpgid($pid)) {
            usleep(100000);
        }

        return $this;
    }

    /**
     * Returns searchd path.
     *
     * @return string
     */
    public function getSearchdPath(): string
    {
        return $this->searchdPath;
    }

    /**
     * Set searchd path.
     *
     * @param string $searchdPath
     *
     * @return Daemon
     */
    public function setSearchdPath(string $searchdPath): self
    {
        $this->searchdPath = $searchdPath;

        return $this;
    }

    /**
     * Returns config path.
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * Set config path.
     *
     * @param string $configPath
     *
     * @return Daemon
     */
    public function setConfigPath(string $configPath): self
    {
        $this->configPath = $configPath;

        return $this;
    }

    /**
     * Returns pid path.
     *
     * @return string
     */
    public function getPidPath(): string
    {
        return $this->pidPath;
    }

    /**
     * Set pid path.
     *
     * @param string $pidPath
     *
     * @return Daemon
     */
    public function setPidPath(string $pidPath): self
    {
        $this->pidPath = $pidPath;

        return $this;
    }

    /**
     * Returns start timeout.
     *
     * @return integer
     */
    public function getStartTimeout(): int
    {
        return $this->startTimeout;
    }

    /**
     * Set start timeout.
     *
     * @param integer $startTimeout
     *
     * @return Daemon
     */
    public function setStartTimeout(int $startTimeout): self
    {
        $this->startTimeout = $startTimeout;

        return $this;
    }

    /**
     * Returns stop timeout.
     *
     * @return integer
     */
    public function getStopTimeout(): int
    {
        return $this->stopTimeout;
    }

    /**
     * Set stop timeout.
     *
     * @param integer $stopTimeout
     *
     * @return Daemon
     */
    public function setStopTimeout(int $stopTimeout): self
    {
        $this->stopTimeout = $stopTimeout;

        return $this;
    }
}
