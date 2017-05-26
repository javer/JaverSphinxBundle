<?php

namespace Javer\SphinxBundle\Config;

/**
 * Class Indexer
 *
 * @package Javer\SphinxBundle\Config
 */
class Indexer extends Block
{
    /**
     * Indexer constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this
            ->setBlockType('indexer')
            ->setOptions($options);
    }
}
