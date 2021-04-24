<?php

namespace Javer\SphinxBundle\Behat\Context;

use Behat\Behat\Context\Context;
use Javer\SphinxBundle\Sphinx\Daemon;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseSphinxContext implements Context
{
    /**
     * Create search index and run sphinx.
     *
     * @Given I create search index and run sphinx
     */
    public function createSearchIndexAndRunSphinx(): void
    {
        $container = $this->getTestContainer();

        $container->get('sphinx')->closeConnection();

        $dataDir = $container->getParameter('javer_sphinx.data_dir');
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $configPath = $dataDir . '/sphinx.conf';

        /** @var Daemon $daemon */
        $daemon = $container->get('sphinx.daemon')->setConfigPath($configPath);
        $daemon->stop();

        $indexes = $container->get('sphinx.converter.mysql_to_realtime')
            ->convertConfig($container->getParameter('javer_sphinx.config_path'), $configPath);

        $daemon->start();

        $container->get('sphinx.loader.doctrine')->loadDataIntoIndexes($indexes);
    }

    /**
     * After Scenario
     *
     * @AfterScenario
     */
    public function afterScenario(): void
    {
        $this->getTestContainer()->get('sphinx')->closeConnection();

        $this->getTestContainer()->get('sphinx.daemon')->stop();
    }

    abstract protected function getTestContainer(): ContainerInterface;
}
