<?php

namespace Javer\SphinxBundle\Config;

/**
 * Class Source
 *
 * @package Javer\SphinxBundle\Config
 */
class Source extends Block
{
    /**
     * Source constructor.
     *
     * @param string      $blockName
     * @param string|null $blockParent
     * @param array       $options
     */
    public function __construct(string $blockName, string $blockParent = null, array $options = [])
    {
        $this
            ->setBlockType('source')
            ->setBlockName($blockName)
            ->setBlockParent($blockParent)
            ->setOptions($options);
    }

    /**
     * Returns merged options.
     *
     * @return array
     */
    public function getMergedOptions(): array
    {
        $options = $this->getOptions();

        if (
            $this->blockParent !== null
            && $this->config !== null
            && $parent = $this->config->getSourceByName($this->blockParent)
        ) {
            return array_merge($parent->getMergedOptions(), $options);
        }

        return $options;
    }
}
