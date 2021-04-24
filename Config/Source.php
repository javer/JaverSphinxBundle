<?php

namespace Javer\SphinxBundle\Config;

class Source extends Block
{
    /**
     * Source constructor.
     *
     * @param string                       $blockName
     * @param string|null                  $blockParent
     * @param array<array{string, string}> $options
     */
    public function __construct(string $blockName, ?string $blockParent = null, array $options = [])
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
     * @return array<array{string, string}>
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
