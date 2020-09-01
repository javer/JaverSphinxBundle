<?php

namespace Javer\SphinxBundle\Config;

/**
 * Class Config
 *
 * @package Javer\SphinxBundle\Config
 */
class Config
{
    /**
     * @var Source[]
     */
    protected $sources;

    /**
     * @var Index[]
     */
    protected $indexes;

    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @var Daemon
     */
    protected $daemon;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->sources = [];
        $this->indexes = [];
    }

    /**
     * Create configuration from the given string.
     *
     * @param string $config
     *
     * @return Config
     */
    public static function fromString(string $config): self
    {
        return static::parse($config);
    }

    /**
     * Create configuration from the given file.
     *
     * @param string $filename
     *
     * @return Config
     */
    public static function fromFile(string $filename): self
    {
        return static::parse(file_get_contents($filename));
    }

    /**
     * Parse config.
     *
     * @param string $configText
     *
     * @return Config
     */
    protected static function parse(string $configText): self
    {
        $configText = str_replace(["\r", "\n\n", "\\\n"], ["\n", "\n", ''], $configText);
        $configText = preg_replace('/#[^!].*?$/', '', $configText);

        $config = new static();

        if (preg_match_all('/([^{}]+)\{([^}]+)\}/m', $configText, $matches, PREG_SET_ORDER)) {
            foreach ($matches as [, $blockHeader, $blockContent]) {
                $blockHeader = trim($blockHeader);
                $blockName = null;
                $blockParent = null;
                $blockContent = trim($blockContent);
                $options = [];

                if (strpos($blockHeader, ' ') === false) {
                    $blockType = $blockHeader;
                } else {
                    [$blockType, $blockName] = explode(' ', $blockHeader, 2);

                    if (strpos($blockName, ':') !== false) {
                        [$blockName, $blockParent] = explode(':', str_replace(' ', '', $blockName), 2);
                    }
                }

                if (preg_match_all('/^\s*(.*?)\s*=\s*(.*?)\s*$/m', $blockContent, $optionsMatches, PREG_SET_ORDER)) {
                    foreach ($optionsMatches as [, $optionName, $optionValue]) {
                        $options[] = [$optionName, $optionValue];
                    }
                }

                switch (strtolower($blockType)) {
                    case 'source':
                        $config->addSource(new Source($blockName, $blockParent, $options));
                        break;

                    case 'index':
                        $config->addIndex(new Index($blockName, $blockParent, $options));
                        break;

                    case 'indexer':
                        $config->setIndexer(new Indexer($options));
                        break;

                    case 'searchd':
                        $config->setDaemon(new Daemon($options));
                        break;
                }
            }
        }

        return $config;
    }

    /**
     * Add source.
     *
     * @param Source $source
     *
     * @return Config
     */
    public function addSource(Source $source): self
    {
        $this->sources[] = $source->setConfig($this);

        return $this;
    }

    /**
     * Returns sources.
     *
     * @return Source[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Returns source by name.
     *
     * @param string $name
     *
     * @return Source|null
     */
    public function getSourceByName(string $name): ?Source
    {
        foreach ($this->sources as $source) {
            if ($source->getBlockName() === $name) {
                return $source;
            }
        }

        return null;
    }

    /**
     * Add index.
     *
     * @param Index $index
     *
     * @return Config
     */
    public function addIndex(Index $index): self
    {
        $this->indexes[] = $index->setConfig($this);

        return $this;
    }

    /**
     * Returns indexes.
     *
     * @return Index[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Returns index by name.
     *
     * @param string $name
     *
     * @return Index|null
     */
    public function getIndexByName(string $name): ?Index
    {
        foreach ($this->indexes as $index) {
            if ($index->getBlockName() === $name) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Set indexer.
     *
     * @param Indexer $indexer
     *
     * @return Config
     */
    public function setIndexer(Indexer $indexer = null): self
    {
        $this->indexer = $indexer ? $indexer->setConfig($this) : null;

        return $this;
    }

    /**
     * Returns indexer.
     *
     * @return Indexer|null
     */
    public function getIndexer(): ?Indexer
    {
        return $this->indexer;
    }

    /**
     * Set daemon.
     *
     * @param Daemon|null $daemon
     *
     * @return Config
     */
    public function setDaemon(Daemon $daemon = null): self
    {
        $this->daemon = $daemon ? $daemon->setConfig($this) : null;

        return $this;
    }

    /**
     * Returns daemon.
     *
     * @return Daemon|null
     */
    public function getDaemon(): ?Daemon
    {
        return $this->daemon;
    }

    /**
     * Renders config to string.
     *
     * @return string
     */
    public function toString(): string
    {
        $config = '';

        foreach ($this->sources as $source) {
            $config .= $source->toString() . "\n";
        }

        foreach ($this->indexes as $index) {
            $config .= $index->toString() . "\n";
        }

        if ($this->indexer) {
            $config .= $this->indexer->toString() . "\n";
        }

        if ($this->daemon) {
            $config .= $this->daemon->toString() . "\n";
        }

        return $config;
    }

    /**
     * Save config to file.
     *
     * @param string $filename
     *
     * @return Config
     */
    public function saveToFile(string $filename): self
    {
        file_put_contents($filename, $this->toString());

        return $this;
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Clones the current object.
     */
    public function __clone()
    {
        foreach ($this->sources as $k => $source) {
            $this->sources[$k] = (clone $source)->setConfig($this);
        }

        foreach ($this->indexes as $k => $index) {
            $this->indexes[$k] = (clone $index)->setConfig($this);
        }

        if ($this->indexer !== null) {
            $this->indexer = (clone $this->indexer)->setConfig($this);
        }

        if ($this->daemon !== null) {
            $this->daemon = (clone $this->daemon)->setConfig($this);
        }
    }
}
