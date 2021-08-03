<?php

namespace Javer\SphinxBundle\DependencyInjection;

use Javer\SphinxBundle\Sphinx\Daemon\DaemonInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class JaverSphinxExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param mixed[]          $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('javer_sphinx.host', $config['host']);
        $container->setParameter('javer_sphinx.port', $config['port']);
        $container->setParameter('javer_sphinx.config_path', $config['config_path']);
        $container->setParameter('javer_sphinx.data_dir', $config['data_dir']);
        $container->setParameter('javer_sphinx.searchd_path', $config['searchd_path']);
        $container->setParameter('javer_sphinx.docker_image', $config['docker_image']);

        if (is_string($config['docker_image']) && $config['docker_image'] !== '') {
            $daemonDefinition = 'sphinx.daemon.docker';
        } else {
            $daemonDefinition = 'sphinx.daemon.native';
        }

        $container->setDefinition('sphinx.daemon', new ChildDefinition($daemonDefinition));
        $container->setAlias(DaemonInterface::class, 'sphinx.daemon');
    }
}
