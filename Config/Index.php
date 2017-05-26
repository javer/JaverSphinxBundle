<?php

namespace Javer\SphinxBundle\Config;

/**
 * Class Index
 *
 * @package Javer\SphinxBundle\Config
 */
class Index extends Block
{
    /**
     * Index constructor.
     *
     * @param string $blockName
     * @param string $blockParent
     * @param array  $options
     */
    public function __construct(string $blockName, string $blockParent = null, array $options = [])
    {
        $this
            ->setBlockType('index')
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

        if (!is_null($this->blockParent) && !is_null($this->config)) {
            if ($parent = $this->config->getIndexByName($this->blockParent)) {
                return array_merge($parent->getMergedOptions(), $options);
            }
        }

        return $options;
    }
}
