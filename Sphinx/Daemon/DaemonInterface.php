<?php

namespace Javer\SphinxBundle\Sphinx\Daemon;

interface DaemonInterface
{
    public function start(): void;

    public function stop(): void;

    public function isRunning(): bool;

    public function setConfigPath(string $configPath): void;
}
