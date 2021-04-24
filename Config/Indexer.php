<?php

namespace Javer\SphinxBundle\Config;

class Indexer extends Block
{
    /**
     * Indexer constructor.
     *
     * @param array<array{string, string}> $options
     */
    public function __construct(array $options = [])
    {
        $this
            ->setBlockType('indexer')
            ->setOptions($options);
    }
}
