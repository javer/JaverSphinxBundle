<?php

namespace Javer\SphinxBundle\Tests\Sphinx;

use Javer\SphinxBundle\Logger\SphinxLogger;
use Javer\SphinxBundle\Sphinx\Query;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class QueryTest
 *
 * @package Javer\SphinxBundle\Tests\Sphinx
 */
class QueryTest extends TestCase
{
    /**
     * Creates a new Query.
     *
     * @return Query
     */
    private function createQuery(): Query
    {
        $pdo = $this->createPartialMock(PDO::class, ['quote']);

        $pdo
            ->method('quote')
            ->willReturnCallback(static function ($value) {
                return is_string($value) ? '"' . str_replace('"', '\"', $value) . '"' : $value;
            });

        $logger = $this->createMock(SphinxLogger::class);

        /** @var Query|MockObject $query */
        $query = $this->getMockBuilder(Query::class)
            ->setConstructorArgs([$pdo, $logger])
            ->setMethods()
            ->getMock();

        return $query;
    }

    /**
     * Tests select query.
     */
    public function testSelectQuery(): void
    {
        $actualSql = $this->createQuery()
            ->select('id', 'column1', 'column2', 'WEIGHT() as weight')
            ->from('index1', 'index2')
            ->where('column3', 'value1')
            ->where('column4', '>', 4)
            ->where('column5', [5, '6'])
            ->where('column6', 'NOT IN', [7, '8'])
            ->where('column7', 'BETWEEN', [9, 10])
            ->match('column8', 'value2')
            ->match(['column9', 'column10'], 'value3')
            ->groupBy('column11')
            ->groupBy('column12')
            ->withinGroupOrderBy('column13', 'desc')
            ->withinGroupOrderBy('column14')
            ->having('weight', '>', 2)
            ->orderBy('column15', 'desc')
            ->orderBy('column16')
            ->offset('5')
            ->limit(10)
            ->option('agent_query_timeout', 10000)
            ->option('max_matches', 1000)
            ->option('field_weights', '(column9=10, column10=3)')
            ->getQuery();

        $expectedSql = 'SELECT id, column1, column2, WEIGHT() as weight'
            . ' FROM index1, index2'
            . ' WHERE column3 = "value1"'
            . ' AND column4 > 4'
            . ' AND column5 IN (5, "6")'
            . ' AND column6 NOT IN (7, "8")'
            . ' AND column7 BETWEEN 9 AND 10'
            . ' AND MATCH("@column8 value2 @(column9,column10) value3")'
            . ' GROUP BY column11, column12'
            . ' WITHIN GROUP ORDER BY column13 DESC, column14 ASC'
            . ' HAVING weight > 2'
            . ' ORDER BY column15 DESC, column16 ASC'
            . ' LIMIT 5, 10'
            . ' OPTION agent_query_timeout = 10000, max_matches = 1000, field_weights = (column9=10, column10=3)';

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test raw query.
     */
    public function testRawQuery(): void
    {
        $actualSql = $this->createQuery()
            ->setQuery('DESCRIBE index1')
            ->getQuery();

        $expectedSql = 'DESCRIBE index1';

        self::assertEquals($expectedSql, $actualSql);
    }

    /**
     * Test quote value.
     */
    public function testQuoteValue(): void
    {
        $query = $this->createQuery();

        self::assertSame(0, $query->quoteValue(false));
        self::assertSame(1, $query->quoteValue(true));
        self::assertSame(2, $query->quoteValue(2));
        self::assertSame('"3"', $query->quoteValue('3'));
        self::assertSame('"4a"', $query->quoteValue('4a'));
    }
}
