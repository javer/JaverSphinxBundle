<?php

namespace Javer\SphinxBundle\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SphinxContext
 *
 * @package Javer\SphinxBundle\Behat\Context
 */
class SphinxContext extends BaseSphinxContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * {@inheritDoc}
     */
    protected function getTestContainer(): ContainerInterface
    {
        $container = $this->getContainer();

        return $container->has('test.container') ? $container->get('test.container') : $container;
    }
}
