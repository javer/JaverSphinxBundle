<?php

namespace Javer\SphinxBundle\Config;

class Daemon extends Block
{
    /**
     * Daemon constructor.
     *
     * @param array<array{string, string}> $options
     */
    public function __construct(array $options = [])
    {
        $this
            ->setBlockType('searchd')
            ->setOptions($options);
    }
}
