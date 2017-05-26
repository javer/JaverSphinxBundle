<?php

namespace Javer\SphinxBundle\Sphinx;

use Doctrine\ORM\QueryBuilder;
use Javer\SphinxBundle\Logger\SphinxLogger;
use PDO;
use PDOStatement;

/**
 * Class Query
 *
 * @package Javer\SphinxBundle\Sphinx
 */
class Query
{
    const CONDITION_OPERATORS = ['=', '!=', '<', '>', '<=', '>=', 'IN', 'NOT IN', 'BETWEEN'];

    /**
     * @var PDO
     */
    protected $connection;

    /**
     * @var SphinxLogger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var string
     */
    protected $queryBuilderAlias;

    /**
     * @var string
     */
    protected $queryBuilderColumn;

    /**
     * @var array
     */
    protected $select = [];

    /**
     * @var array
     */
    protected $from = [];

    /**
     * @var array
     */
    protected $where = [];

    /**
     * @var array
     */
    protected $match = [];

    /**
     * @var array
     */
    protected $groupBy = [];

    /**
     * @var array
     */
    protected $withinGroupOrderBy = [];

    /**
     * @var array
     */
    protected $having = [];

    /**
     * @var array
     */
    protected $orderBy = [];

    /**
     * @var integer
     */
    protected $offset = 0;

    /**
     * @var integer
     */
    protected $limit = 20;

    /**
     * @var array
     */
    protected $option = [];

    /**
     * @var array|null
     */
    protected $results;

    /**
     * @var integer
     */
    protected $numRows;

    /**
     * @var array|null
     */
    protected $metadata;

    /**
     * Query constructor.
     *
     * @param PDO          $connection
     * @param SphinxLogger $logger
     * @param string       $query
     */
    public function __construct(PDO $connection, SphinxLogger $logger, string $query = null)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->query = $query;
    }

    /**
     * Use QueryBuilder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string       $column
     *
     * @return Query
     */
    public function useQueryBuilder(QueryBuilder $queryBuilder, string $alias, string $column = 'id')
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->queryBuilderAlias = $alias;
        $this->queryBuilderColumn = $column;

        if (array_search('id', $this->select) === false && array_search('*', $this->select) === false) {
            $this->select('id');
        }

        return $this;
    }

    /**
     * Sets query.
     *
     * @param string $query
     *
     * @return Query
     */
    public function setQuery(string $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Add SELECT clause.
     *
     * @param array ...$columns
     *
     * @return Query
     */
    public function select(...$columns)
    {
        $this->select = array_merge($this->select, $columns ?: ['*']);

        return $this;
    }

    /**
     * Add FROM clause.
     *
     * @param array ...$indexes
     *
     * @return Query
     */
    public function from(...$indexes)
    {
        $this->from = array_merge($this->from, $indexes);

        return $this;
    }

    /**
     * Creates a new condition for WHERE or HAVING clause.
     *
     * @param string $column
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function createCondition(string $column, $operator, $value = null)
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = is_array($value) ? 'IN' : '=';
        }

        $operator = strtoupper($operator);

        if (!in_array($operator, self::CONDITION_OPERATORS)) {
            throw new \InvalidArgumentException(sprintf('Invalid operator %s', $operator));
        }

        if ($operator === 'BETWEEN' && (!is_array($value) || count($value) != 2)) {
            throw new \InvalidArgumentException('BETWEEN operator expects an array with exactly 2 values');
        }

        if (in_array($operator, ['IN', 'NOT IN']) && !is_array($value)) {
            throw new \InvalidArgumentException('IN operator expects an array with values');
        }

        return [$column, $operator, $value];
    }

    /**
     * Add WHERE clause.
     *
     * @param string $column
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return Query
     */
    public function where(string $column, $operator, $value = null)
    {
        $this->where[] = $this->createCondition($column, $operator, $value);

        return $this;
    }

    /**
     * Add MATCH clause.
     *
     * @param string|string[] $column
     * @param string          $value
     *
     * @return Query
     */
    public function match($column, $value)
    {
        $this->match[] = [$column, $value];

        return $this;
    }

    /**
     * Group by column.
     *
     * @param string $column
     *
     * @return Query
     */
    public function groupBy(string $column)
    {
        $this->groupBy[] = $column;

        return $this;
    }

    /**
     * Within group order by column.
     *
     * @param string      $column
     * @param string|null $direction
     *
     * @return Query
     */
    public function withinGroupOrderBy(string $column, $direction = null)
    {
        $this->withinGroupOrderBy[] = [
            $column,
            (!is_null($direction) && strtoupper($direction) === 'DESC') ? 'DESC' : 'ASC'
        ];

        return $this;
    }

    /**
     * Add HAVING clause.
     *
     * @param string $column
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return Query
     */
    public function having(string $column, $operator, $value = null)
    {
        $this->having[] = $this->createCondition($column, $operator, $value);

        return $this;
    }

    /**
     * Order by column.
     *
     * @param string      $column
     * @param string|null $direction
     *
     * @return Query
     */
    public function orderBy(string $column, $direction = null)
    {
        $this->orderBy[] = [
            $column,
            (!is_null($direction) && strtoupper($direction) === 'DESC') ? 'DESC' : 'ASC'
        ];

        return $this;
    }

    /**
     * Set offset.
     *
     * @param integer $offset
     *
     * @return Query
     *
     * @throws \InvalidArgumentException
     */
    public function offset(int $offset)
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset should be bigger or equal to zero');
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * Set limit.
     *
     * @param integer      $offset
     * @param integer|null $limit
     *
     * @return Query
     *
     * @throws \InvalidArgumentException
     */
    public function limit(int $offset, int $limit = null)
    {
        if (!is_null($limit)) {
            if ($offset < 0) {
                throw new \InvalidArgumentException('Offset should be bigger or equal to zero');
            }

            if ($limit <= 0) {
                throw new \InvalidArgumentException('Limit should be positive');
            }

            $this->limit = $limit;
            $this->offset = $offset;
        } else {
            if ($offset <= 0) {
                throw new \InvalidArgumentException('Limit should be positive');
            }

            $this->limit = $offset;
        }

        return $this;
    }

    /**
     * Add option.
     *
     * @param string $name
     * @param string $value
     *
     * @return Query
     */
    public function option(string $name, string $value)
    {
        $this->option[] = [$name, $value];

        return $this;
    }

    /**
     * Quote value.
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return string
     */
    public function quoteValue($value, $type = null)
    {
        if (is_int($value)) {
            return (int) $value;
        } elseif (is_bool($value)) {
            return (bool) $value;
        } else {
            return $this->connection->quote($value, $type);
        }
    }

    /**
     * Quote match.
     *
     * @param string  $value
     * @param boolean $isText
     *
     * @return string
     */
    protected function quoteMatch(string $value, $isText = false)
    {
        return addcslashes($value, $isText ? '\()!@~&/^$=<>' : '\()|-!@~"&/^$=<>');
    }

    /**
     * Returns a plain SQL query.
     *
     * @return string
     */
    public function getQuery(): string
    {
        if (is_null($this->query)) {
            $this->query = $this->buildQuery();
        }

        return $this->query;
    }

    /**
     * Build a query.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function buildQuery(): string
    {
        if (!$this->select) {
            throw new \InvalidArgumentException('You should add at least one SELECT clause');
        }

        if (!$this->from) {
            throw new \InvalidArgumentException('You should add at least one FROM clause');
        }

        $clauses = [];

        $clauses[] = 'SELECT ' . implode(', ', $this->select);

        $clauses[] = 'FROM ' . implode(', ', $this->from);

        if ($this->where) {
            $clauses[] = 'WHERE ' . $this->buildCondition($this->where);
        }

        if ($this->match) {
            $clauses[] = ($this->where ? 'AND ' : 'WHERE ') . $this->buildMatch($this->match);
        }

        if ($this->groupBy) {
            $clauses[] = 'GROUP BY ' . implode(', ', $this->groupBy);
        }

        if ($this->withinGroupOrderBy) {
            $clauses[] = 'WITHIN GROUP ORDER BY ' . $this->buildOrder($this->withinGroupOrderBy);
        }

        if ($this->having) {
            $clauses[] = 'HAVING ' . $this->buildCondition($this->having);
        }

        if ($this->orderBy) {
            $clauses[] = 'ORDER BY ' . $this->buildOrder($this->orderBy);
        }

        $clauses[] = sprintf('LIMIT %d, %d', $this->offset, $this->limit);

        if ($this->option) {
            $clauses[] = 'OPTION ' . $this->buildOption($this->option);
        }

        return trim(implode(' ', $clauses));
    }

    /**
     * Builds condition clause.
     *
     * @param array $conditions
     *
     * @return string
     */
    protected function buildCondition(array $conditions)
    {
        $pieces = [];

        foreach ($conditions as [$column, $operator, $value]) {
            if ($operator === 'BETWEEN') {
                $value = $this->quoteValue($value[0]) . ' AND ' . $this->quoteValue($value[1]);
            } elseif (is_array($value)) {
                $value = '(' . implode(', ', array_map('intval', $value)) . ')';
            } else {
                $value = $this->quoteValue($value);
            }

            $pieces[] = $column . ' ' . $operator . ' ' . $value;
        }

        return implode(' AND ', $pieces);
    }

    /**
     * Builds match clause.
     *
     * @param array $matches
     *
     * @return string
     */
    protected function buildMatch(array $matches)
    {
        $pieces = [];

        foreach ($matches as [$column, $value]) {
            if (is_array($column)) {
                $column = '(' . implode(',', array_map([$this, 'quoteMatch'], $column)) . ')';
            } else {
                $column = $this->quoteMatch($column);
            }

            $pieces[] = sprintf('@%s %s', $column, $this->quoteMatch($value, true));
        }

        return sprintf('MATCH(%s)', $this->quoteValue(implode(' ', $pieces)));
    }

    /**
     * Builds order clause.
     *
     * @param array $orders
     *
     * @return string
     */
    protected function buildOrder(array $orders)
    {
        $pieces = [];

        foreach ($orders as [$column, $direction]) {
            $pieces[] = $column . ' ' . $direction;
        }

        return implode(', ', $pieces);
    }

    /**
     * Builds option clause.
     *
     * @param array $options
     *
     * @return string
     */
    protected function buildOption(array $options)
    {
        $pieces = [];

        foreach ($options as [$name, $value]) {
            $pieces[] = $name . ' = ' . $value;
        }

        return implode(', ', $pieces);
    }

    /**
     * Returns an array of results.
     *
     * @return array
     */
    public function getResults(): array
    {
        if (is_null($this->results)) {
            $this->execute();

            if ($this->queryBuilder) {
                $this->results = $this->applyQueryBuilder($this->results);
            }
        }

        return $this->results;
    }

    /**
     * Executes query and returns number of affected rows.
     *
     * @return integer
     */
    public function execute()
    {
        $startTime = microtime(true);

        $this->results = [];
        $this->numRows = 0;

        $stmt = $this->createStatement($this->getQuery());

        if ($stmt->execute()) {
            if ($this->select) {
                $this->results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $this->numRows = $stmt->rowCount();

            $stmt->closeCursor();
        }

        $endTime = microtime(true);

        $this->logger->logQuery($this->getQuery(), $this->getNumRows(), $endTime - $startTime);

        return $this->numRows;
    }

    /**
     * Returns number of affected rows.
     *
     * @return integer
     *
     * @throws \BadMethodCallException
     */
    public function getNumRows(): int
    {
        if (is_null($this->numRows)) {
            throw new \BadMethodCallException('You must execute query before getting number of affected rows');
        }

        return $this->numRows;
    }

    /**
     * Creates a new PDO statement.
     *
     * @param string $query
     *
     * @return PDOStatement
     */
    protected function createStatement(string $query): PDOStatement
    {
        return $this->connection->query($query);
    }

    /**
     * Apply QueryBuilder
     *
     * @param array $results
     *
     * @return array
     */
    protected function applyQueryBuilder(array $results): array
    {
        if (count($results) == 0) {
            return [];
        }

        $ids = array_map('intval', array_column($results, 'id'));
        $results = [];

        $paramName = sprintf('%s%sids', $this->queryBuilderAlias, $this->queryBuilderColumn);

        $this->queryBuilder
            ->andWhere(sprintf('%s.%s IN (:%s)', $this->queryBuilderAlias, $this->queryBuilderColumn, $paramName))
            ->setParameter($paramName, $ids)
            ->setFirstResult(null)
            ->setMaxResults(null);

        if ($this->orderBy) {
            $this->queryBuilder->resetDQLPart('orderBy');
        }

        $entities = $this->queryBuilder->getQuery()->getResult();

        foreach ($entities as $entity) {
            $idGetter = 'get' . ucfirst($this->queryBuilderColumn);
            $id = $entity->$idGetter();
            $position = array_search($id, $ids);
            $results[$position] = $entity;
        }

        ksort($results);

        return array_values($results);
    }

    /**
     * Returns query result metadata.
     *
     * @return array
     *
     * @throws \BadMethodCallException
     */
    public function getMetadata(): array
    {
        if (!is_null($this->metadata)) {
            return $this->metadata;
        }

        if (is_null($this->results)) {
            throw new \BadMethodCallException('You can get metadata only after executing query');
        }

        $this->metadata = [];
        $stmt = $this->createStatement('SHOW META');

        if ($stmt->execute()) {
            $this->metadata = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $stmt->closeCursor();
        }

        return $this->metadata;
    }

    /**
     * Returns metadata value.
     *
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed|null
     */
    public function getMetadataValue(string $name, $defaultValue = null)
    {
        $metadata = $this->getMetadata();

        return array_key_exists($name, $metadata) ? $metadata[$name] : $defaultValue;
    }

    /**
     * Returns total count of found rows.
     *
     * @return integer
     */
    public function getTotalFound(): int
    {
        return (int) $this->getMetadataValue('total_found', 0);
    }

    /**
     * Returns executing time.
     *
     * @return float
     */
    public function getTime(): float
    {
        return (float) $this->getMetadataValue('time', 0);
    }

    /**
     * Clones the current object.
     */
    public function __clone()
    {
        $this->results = null;
        $this->numRows = null;
        $this->metadata = null;

        if ($this->queryBuilder) {
            $this->queryBuilder = clone $this->queryBuilder;
        }
    }

    /**
     * Sleep.
     *
     * @return array
     */
    public function __sleep()
    {
        return array_diff(array_keys(get_object_vars($this)), ['connection', 'logger', 'queryBuilder', 'results']);
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getQuery();
    }
}
