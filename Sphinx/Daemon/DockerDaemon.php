<?php

namespace Javer\SphinxBundle\Sphinx\Daemon;

use RuntimeException;

final class DockerDaemon implements DaemonInterface
{
    public function __construct(
        private string $dockerImage,
        private string $configPath,
        private string $dataDir,
        private string $cidPath,
        private int $port,
        private int $startTimeout,
    )
    {
    }

    public function start(): void
    {
        $command = sprintf(
            'docker run --rm --detach -v %s -v %s --cidfile %s --publish %d:%d %s searchd --nodetach -c %s',
            escapeshellarg($this->dataDir . ':' . $this->dataDir),
            escapeshellarg($this->configPath . ':' . $this->configPath),
            escapeshellarg($this->cidPath),
            $this->port,
            $this->port,
            escapeshellarg($this->dockerImage),
            escapeshellarg($this->configPath)
        );

        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            throw new RuntimeException(sprintf('Cannot start sphinx docker container, return code: %d', $resultCode));
        }

        sleep($this->startTimeout);
    }

    public function stop(): void
    {
        $containerId = $this->getContainerId();

        if ($containerId === null) {
            return;
        }

        unlink($this->cidPath);

        $command = sprintf('docker stop %s', escapeshellarg($containerId));

        exec($command, $output, $resultCode);
    }

    public function isRunning(): bool
    {
        $containerId = $this->getContainerId();

        if ($containerId === null) {
            return false;
        }

        $command = sprintf('docker ps -q --filter "id=%s"', $containerId);

        exec($command, $output, $resultCode);

        return $resultCode === 0
            && is_array($output)
            && count($output) === 1
            && str_starts_with($containerId, $output[0]);
    }

    public function setConfigPath(string $configPath): void
    {
        $this->configPath = $configPath;
    }

    private function getContainerId(): ?string
    {
        if ($this->cidPath === '' || !is_readable($this->cidPath)) {
            return null;
        }

        $containerId = trim(file_get_contents($this->cidPath));

        if (empty($containerId) || !preg_match('/^[a-z0-9]+$/i', $containerId)) {
            return null;
        }

        return $containerId;
    }
}
