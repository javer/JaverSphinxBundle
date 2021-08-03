<?php

namespace Javer\SphinxBundle\Sphinx\Daemon;

use RuntimeException;

final class NativeDaemon implements DaemonInterface
{
    public function __construct(
        private string $searchdPath,
        private string $configPath,
        private string $pidPath,
        private int $startTimeout,
        private int $stopTimeout,
    )
    {
    }

    public function start(): void
    {
        $command = sprintf(
            '%s -c %s',
            escapeshellcmd($this->searchdPath),
            escapeshellarg($this->configPath)
        );

        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            throw new RuntimeException(sprintf('Cannot start sphinx daemon, return code: %d', $resultCode));
        }

        sleep($this->startTimeout);
    }

    public function stop(): void
    {
        $pid = $this->getProcessId();

        if ($pid === null) {
            return;
        }

        posix_kill($pid, SIGTERM);

        $endTime = microtime(true) + $this->stopTimeout;

        while ($endTime > microtime(true) && posix_getpgid($pid)) {
            usleep(100000);
        }
    }

    public function isRunning(): bool
    {
        $pid = $this->getProcessId();

        return $pid && posix_kill($pid, 0);
    }

    public function setConfigPath(string $configPath): void
    {
        $this->configPath = $configPath;
    }

    private function getProcessId(): ?int
    {
        if ($this->pidPath === '' || !is_readable($this->pidPath)) {
            return null;
        }

        $pid = (int) file_get_contents($this->pidPath);

        return $pid > 0 ? $pid : null;
    }
}
