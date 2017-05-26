<?php

namespace Javer\SphinxBundle\Config;

/**
 * Class Daemon
 *
 * @package Javer\SphinxBundle\Config
 */
class Daemon extends Block
{
    /**
     * Daemon constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this
            ->setBlockType('searchd')
            ->setOptions($options);
    }
}
