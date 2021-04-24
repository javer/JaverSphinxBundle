<?php

namespace Javer\SphinxBundle\Converter;

use Javer\SphinxBundle\Config\Config;
use Javer\SphinxBundle\Config\Daemon;
use Javer\SphinxBundle\Config\Index;

class MysqlToRealtimeIndexConverter
{
    /**
     * MysqlToRealtimeIndexConverter constructor.
     *
     * @param integer  $port
     * @param string   $dataDir
     * @param string   $pidPath
     * @param string[] $indexOptionsBlacklist
     */
    public function __construct(
        protected int $port,
        protected string $dataDir,
        protected string $pidPath,
        protected array $indexOptionsBlacklist,
    )
    {
    }

    /**
     * Convert config with mysql indexes to realtime indexes.
     *
     * @param string $sourceConfigPath
     * @param string $targetConfigPath
     *
     * @return array
     *
     * @phpstan-return array<string, array{
     *     schema: array<string, string>,
     *     query: string,
     *     joinedFields: array<array{string, string, string}>,
     *     attrMulti: array<array{string, string, string}>
     * }>
     */
    public function convertConfig(string $sourceConfigPath, string $targetConfigPath): array
    {
        $config = Config::fromFile($sourceConfigPath);

        $rtConfig = new Config();

        $indexes = [];

        foreach ($config->getIndexes() as $index) {
            if ($sourceName = $index->getOptionByName('source')) {
                if ($source = $config->getSourceByName($sourceName)) {
                    $sourceOptions = $source->getMergedOptions();

                    $schema = [];
                    $sqlQuery = null;
                    $sqlJoinedFields = [];
                    $sqlAttrMulti = [];
                    $sqlAttrRegexp = '/^(?:#!)?sql_(attr|field)_(uint|bool|bigint|timestamp|float|string|json)$/';

                    foreach ($sourceOptions as [$optionName, $optionValue]) {
                        if (preg_match($sqlAttrRegexp, $optionName, $match)) {
                            $schema[$optionValue] = $match[1] === 'field' ? $match[1] : $match[2];
                        } elseif ($optionName === 'sql_query') {
                            $sqlQuery = $optionValue;
                        } elseif ($optionName === 'sql_joined_field') {
                            // FIELD-NAME 'from' ( 'query' | 'payload-query' | 'ranged-query' ); QUERY [ ; RANGE-QUERY ]
                            $pieces = array_map('trim', explode(';', $optionValue));
                            if (preg_match('/^(\S+) from (\S+)$/', $pieces[0], $joinedMatch)) {
                                // queryType, fieldName, joinedQuery
                                $sqlJoinedFields[] = [strtolower($joinedMatch[2]), $joinedMatch[1], $pieces[1]];
                                $schema[$joinedMatch[1]] = 'field';
                            }
                        } elseif ($optionName === 'sql_attr_multi') {
                            // sql_attr_multi = ATTR-TYPE ATTR-NAME 'from' SOURCE-TYPE [;QUERY] [;RANGE-QUERY]
                            $pieces = array_map('trim', explode(';', $optionValue));
                            if (preg_match('/^(\S+) (\S+) from (\S+)$/', $pieces[0], $multiMatch)) {
                                // attrType, attrName, attrQuery
                                $sqlAttrMulti[] = [$multiMatch[1], $multiMatch[2], $pieces[1]];
                                $schema[$multiMatch[2]] = 'multi';
                            }
                        }
                    }

                    if ($sqlQuery) {
                        $newIndex = new Index($index->getBlockName());
                        $rtConfig->addIndex($newIndex);
                        $newIndex->addOption('type', 'rt');
                        $newIndex->addOption('path', $this->getDataDir() . '/' . $index->getBlockName());

                        foreach ($index->getMergedOptions() as [$optionName, $optionValue]) {
                            if (!in_array($optionName, $this->getIndexOptionsBlacklist(), true)) {
                                $newIndex->addOption($optionName, $optionValue);
                            }
                        }

                        foreach ($schema as $fieldName => $fieldType) {
                            if ($fieldType === 'field') {
                                $newIndex->addOption('rt_field', $fieldName);
                            } else {
                                $newIndex->addOption('rt_attr_' . $fieldType, $fieldName);
                            }
                        }

                        $indexes[$index->getBlockName()] = [
                            'schema' => $schema,
                            'query' => $sqlQuery,
                            'joinedFields' => $sqlJoinedFields,
                            'attrMulti' => $sqlAttrMulti,
                        ];
                    }
                }
            } elseif ($index->getOptionByName('type', 'plain') !== 'plain') {
                $rtConfig->addIndex(clone $index);
            }
        }

        $daemon = new Daemon();
        $daemon->addOption('listen', $this->getPort() . ':mysql41');
        $daemon->addOption('log', $this->getDataDir() . '/searchd.log');
        $daemon->addOption('query_log', $this->getDataDir() . '/query.log');
        $daemon->addOption('query_log_format', 'sphinxql');
        $daemon->addOption('pid_file', $this->getPidPath());
        $daemon->addOption('binlog_path', $this->getDataDir());
        $rtConfig->setDaemon($daemon);

        $rtConfig->saveToFile($targetConfigPath);

        return $indexes;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function getDataDir(): string
    {
        return $this->dataDir;
    }

    public function setDataDir(string $dataDir): self
    {
        $this->dataDir = $dataDir;

        return $this;
    }

    public function getPidPath(): string
    {
        return $this->pidPath;
    }

    public function setPidPath(string $pidPath): self
    {
        $this->pidPath = $pidPath;

        return $this;
    }

    /**
     * Returns indexOptionsBlacklist.
     *
     * @return string[]
     */
    public function getIndexOptionsBlacklist(): array
    {
        return $this->indexOptionsBlacklist;
    }

    /**
     * Set indexOptionsBlacklist.
     *
     * @param string[] $indexOptionsBlacklist
     *
     * @return self
     */
    public function setIndexOptionsBlacklist(array $indexOptionsBlacklist): self
    {
        $this->indexOptionsBlacklist = $indexOptionsBlacklist;

        return $this;
    }
}
