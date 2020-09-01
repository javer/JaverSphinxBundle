<?php

namespace Javer\SphinxBundle\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Javer\SphinxBundle\Sphinx\Daemon;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SphinxContext
 *
 * @package Javer\SphinxBundle\Behat\Context
 */
class SphinxContext implements Context, KernelAwareContext
{
    use KernelDictionary;

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

    /**
     * Returns test container.
     *
     * @return ContainerInterface
     */
    protected function getTestContainer(): ContainerInterface
    {
        $container = $this->getContainer();

        return $container->has('test.container') ? $container->get('test.container') : $container;
    }
}
