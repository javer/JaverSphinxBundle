<?php

namespace Javer\SphinxBundle\Config;

/**
 * Class Block
 *
 * @package Javer\SphinxBundle\Config
 */
abstract class Block
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $blockType;

    /**
     * @var string|null
     */
    protected $blockName;

    /**
     * @var string|null
     */
    protected $blockParent;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Return config instance.
     *
     * @return Config|null
     */
    public function getConfig(): ?Config
    {
        return $this->config;
    }

    /**
     * Set config instance.
     *
     * @param Config $config
     *
     * @return Block
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Returns block type.
     *
     * @return string
     */
    public function getBlockType(): string
    {
        return $this->blockType;
    }

    /**
     * Set block type.
     *
     * @param string $blockType
     *
     * @return Block
     */
    public function setBlockType(string $blockType)
    {
        $this->blockType = $blockType;

        return $this;
    }

    /**
     * Returns block name.
     *
     * @return string
     */
    public function getBlockName(): ?string
    {
        return $this->blockName;
    }

    /**
     * Set block name.
     *
     * @param string $blockName
     *
     * @return Block
     */
    public function setBlockName(string $blockName = null)
    {
        $this->blockName = $blockName;

        return $this;
    }

    /**
     * Returns block parent name.
     *
     * @return string|null
     */
    public function getBlockParent(): ?string
    {
        return $this->blockParent;
    }

    /**
     * Set block parent name.
     *
     * @param string $blockParent
     *
     * @return Block
     */
    public function setBlockParent(string $blockParent = null)
    {
        $this->blockParent = $blockParent;

        return $this;
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns option value by name.
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return array|string|null
     */
    public function getOptionByName(string $name, $defaultValue = null)
    {
        $value = null;

        foreach ($this->options as [$optionName, $optionValue]) {
            if ($optionName === $name) {
                if (is_null($value)) {
                    $value = $optionValue;
                } elseif (is_array($value)) {
                    $value[] = $optionValue;
                } else {
                    $value = [$value, $optionValue];
                }
            }
        }

        return !is_null($value) ? $value : $defaultValue;
    }

    /**
     * Set options.
     *
     * @param array $options
     *
     * @return Block
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Add option.
     *
     * @param string $name
     * @param string $value
     *
     * @return Block
     */
    public function addOption(string $name, string $value)
    {
        $this->options[] = [$name, $value];

        return $this;
    }

    /**
     * Renders block to string.
     *
     * @return string
     */
    public function toString()
    {
        $block = $this->blockType;

        if (!is_null($this->blockName)) {
            $block .= ' ' . $this->blockName;
        }

        if (!is_null($this->blockParent)) {
            $block .= ' : ' . $this->blockParent;
        }

        $block .= "\n{\n";

        foreach ($this->options as [$optionName, $optionValue]) {
            $block .= sprintf("    %s = %s\n", $optionName, $optionValue);
        }

        $block .= "}\n";

        return $block;
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Clones the current object.
     */
    public function __clone()
    {
        $this->config = null;
    }

    /**
     * Sleep.
     *
     * @return array
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), ['config']);
    }
}
