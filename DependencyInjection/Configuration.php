<?php

namespace Javer\SphinxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('javer_sphinx');

        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                ->scalarNode('port')->defaultValue(9306)->end()
                ->scalarNode('config_path')->defaultValue('%kernel.project_dir%/config/sphinx.conf')->end()
                ->scalarNode('data_dir')->defaultValue('%kernel.cache_dir%/sphinx')->end()
                ->scalarNode('searchd_path')->defaultValue('searchd')->end()
                ->scalarNode('docker_image')->defaultValue('')->end()
            ->end();

        return $treeBuilder;
    }
}
