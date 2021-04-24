<?php

namespace Javer\SphinxBundle\Behat\Context;

use Symfony\Component\DependencyInjection\ContainerInterface;

class SphinxDIContext extends BaseSphinxContext
{
    public function __construct(
        private ContainerInterface $container,
    )
    {
    }

    protected function getTestContainer(): ContainerInterface
    {
        return $this->container;
    }
}
