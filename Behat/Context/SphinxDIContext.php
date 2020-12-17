<?php

namespace Javer\SphinxBundle\Behat\Context;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SphinxDIContext
 *
 * @package Javer\SphinxBundle\Behat\Context
 */
class SphinxDIContext extends BaseSphinxContext
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * SphinxDIContext constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    protected function getTestContainer(): ContainerInterface
    {
        return $this->container;
    }
}
