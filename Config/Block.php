<?php

namespace Javer\SphinxBundle\Config;

abstract class Block
{
    protected ?Config $config = null;

    protected string $blockType;

    protected ?string $blockName = null;

    protected ?string $blockParent = null;

    /**
     * @var array<array{string, string}>
     */
    protected array $options = [];

    public function getConfig(): ?Config
    {
        return $this->config;
    }

    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getBlockType(): string
    {
        return $this->blockType;
    }

    public function setBlockType(string $blockType): static
    {
        $this->blockType = $blockType;

        return $this;
    }

    public function getBlockName(): ?string
    {
        return $this->blockName;
    }

    public function setBlockName(?string $blockName): static
    {
        $this->blockName = $blockName;

        return $this;
    }

    public function getBlockParent(): ?string
    {
        return $this->blockParent;
    }

    public function setBlockParent(?string $blockParent): static
    {
        $this->blockParent = $blockParent;

        return $this;
    }

    /**
     * Returns options.
     *
     * @return array<array{string, string}>
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
     * @return string[]|string|null
     */
    public function getOptionByName(string $name, mixed $defaultValue = null)
    {
        $value = null;

        foreach ($this->options as [$optionName, $optionValue]) {
            if ($optionName === $name) {
                if ($value === null) {
                    $value = $optionValue;
                } elseif (is_array($value)) {
                    $value[] = $optionValue;
                } else {
                    $value = [$value, $optionValue];
                }
            }
        }

        return $value ?? $defaultValue;
    }

    /**
     * Set options.
     *
     * @param array<array{string, string}> $options
     *
     * @return static
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function addOption(string $name, string $value): static
    {
        $this->options[] = [$name, $value];

        return $this;
    }

    public function toString(): string
    {
        $block = $this->blockType;

        if ($this->blockName !== null) {
            $block .= ' ' . $this->blockName;
        }

        if ($this->blockParent !== null) {
            $block .= ' : ' . $this->blockParent;
        }

        $block .= "\n{\n";

        foreach ($this->options as [$optionName, $optionValue]) {
            $block .= sprintf("    %s = %s\n", $optionName, $optionValue);
        }

        $block .= "}\n";

        return $block;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __clone()
    {
        $this->config = null;
    }

    /**
     * Sleep.
     *
     * @return array<string, string>
     */
    public function __sleep(): array
    {
        return array_diff(array_keys(get_object_vars($this)), ['config']);
    }
}
