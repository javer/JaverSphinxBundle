<?php

namespace Javer\SphinxBundle\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

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
    public function createSearchIndexAndRunSphinx()
    {
        $container = $this->getContainer();

        $dataDir = $container->getParameter('javer_sphinx.data_dir');
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $configPath = $dataDir . '/sphinx.conf';

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
    public function afterScenario()
    {
        $this->getContainer()->get('sphinx.daemon')->stop();
    }
}
