<?php

namespace Javer\SphinxBundle\Loader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use Javer\SphinxBundle\Sphinx\Manager;
use Javer\SphinxBundle\Sphinx\Query;

class DoctrineDataLoader
{
    public function __construct(
        protected Manager $sphinx,
        protected ?Connection $database = null,
    )
    {
    }

    /**
     * Load data from database to indexes.
     *
     * @param array $indexes
     *
     * @phpstan-param array<string, array{
     *     schema: array<string, string>,
     *     query: string,
     *     joinedFields: array<array{string, string, string}>,
     *     attrMulti: array<array{string, string, string}>
     * }> $indexes
     */
    public function loadDataIntoIndexes(array $indexes): void
    {
        foreach ($indexes as $indexName => $indexData) {
            $this->clearIndex($indexName);

            $joinedData = $this->loadDataForJoinedFields($indexData['joinedFields']);

            $this->loadDataForSqlQuery($indexName, $indexData['schema'], $indexData['query'], $joinedData);

            $this->loadDataForAttrMulti($indexName, $indexData['attrMulti']);
        }
    }

    protected function clearIndex(string $indexName): void
    {
        $this->createSphinxQuery('DELETE FROM ' . $indexName . ' WHERE id >= 1')
            ->execute();
    }

    /**
     * Load data for sql_query.
     *
     * @param string                                        $indexName
     * @param array<string, string>                         $schema
     * @param string                                        $sqlQuery
     * @param array<string, array<int, array<int, string>>> $joinedData
     *
     * @return integer
     */
    protected function loadDataForSqlQuery(
        string $indexName,
        array $schema,
        string $sqlQuery,
        array $joinedData = []
    ): int
    {
        $stmt = $this->executeDatabaseQuery($sqlQuery);

        $sphinxQuery = $this->createSphinxQuery();
        $batch = '';
        $columns = [];

        while (($row = $stmt->fetchAssociative()) !== false) {
            if (empty($columns)) {
                $columns = array_merge(array_keys($row), array_keys($joinedData));
            }

            $values = [];
            foreach ($row as $columnName => $columnValue) {
                $columnType = $columnName === 'id' ? 'uint' : ($schema[$columnName] ?? 'string');

                $values[] = $sphinxQuery->quoteValue($this->castValue($columnValue, $columnType));
            }

            if (isset($row['id'])) {
                $docId = (int) $row['id'];

                foreach ($joinedData as $joinedColumnValues) {
                    $values[] = $sphinxQuery->quoteValue(implode(' ', $joinedColumnValues[$docId] ?? []));
                }
            }

            $batch .= ($batch !== '' ? ', ' : '') . '(' . implode(', ', $values) . ')';
        }

        if ($batch === '' || count($columns) === 0) {
            return 0;
        }

        $sql = 'REPLACE INTO ' . $indexName . ' (' . implode(', ', $columns) . ') VALUES ' . $batch;

        return $sphinxQuery->setQuery($sql)->execute();
    }

    /**
     * Load data for sql_attr_multi.
     *
     * @param string                               $indexName
     * @param array<array{string, string, string}> $attrMulti
     */
    protected function loadDataForAttrMulti(string $indexName, array $attrMulti): void
    {
        foreach ($attrMulti as [$attrType, $attrName, $attrQuery]) {
            $stmt = $this->executeDatabaseQuery($attrQuery);

            $multiValues = [];
            $sphinxQuery = $this->createSphinxQuery();

            while (($row = $stmt->fetchNumeric()) !== false) {
                $docId = (int) $row[0];
                $value = $row[1];

                if (!isset($multiValues[$docId])) {
                    $multiValues[$docId] = [];
                }

                $multiValues[$docId][] = $sphinxQuery->quoteValue($this->castValue($value, $attrType));
            }

            foreach ($multiValues as $docId => $values) {
                $sql = sprintf(
                    'UPDATE %s SET %s = (%s) WHERE id = %d',
                    $indexName,
                    $attrName,
                    implode(', ', array_unique($values)),
                    $docId
                );

                $this->createSphinxQuery($sql)->execute();
            }
        }
    }

    /**
     * Load data for sql_joined_field.
     *
     * @param array<array{string, string, string}> $joinedFields
     *
     * @return array<string, array<int, array<int, string>>>
     */
    protected function loadDataForJoinedFields(array $joinedFields): array
    {
        $data = [];

        foreach ($joinedFields as [$queryType, $fieldName, $joinedQuery]) {
            $stmt = $this->executeDatabaseQuery($joinedQuery);
            $data[$fieldName] = [];

            while (($row = $stmt->fetchNumeric()) !== false) {
                $docId = (int) $row[0];
                $value = (string) $row[1];

                if (!isset($data[$fieldName][$docId])) {
                    $data[$fieldName][$docId] = [];
                }

                $data[$fieldName][$docId][] = $value;
            }
        }

        return $data;
    }

    protected function executeDatabaseQuery(string $query): Result
    {
        return $this->database->executeQuery($this->adaptQuery($query));
    }

    protected function adaptQuery(string $query): string
    {
        if ($this->database->getDatabasePlatform() instanceof SqlitePlatform) {
            $query = preg_replace("/UNIX_TIMESTAMP\(/i", "strftime('%s', ", $query);
            $query = preg_replace("/JSON_OBJECTAGG\(/i", "json_group_object(", $query);
        }

        return $query;
    }

    protected function createSphinxQuery(?string $query = null): Query
    {
        $sphinxQuery = $this->sphinx->createQuery();

        if ($query !== null) {
            $sphinxQuery->setQuery($query);
        }

        return $sphinxQuery;
    }

    protected function castValue(mixed $value, string $type): string|int
    {
        if (in_array($type, ['uint', 'bool', 'bigint', 'timestamp'])) {
            return (int) $value;
        }

        return (string) $value;
    }
}
