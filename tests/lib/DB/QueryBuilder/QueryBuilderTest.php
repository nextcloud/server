<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\DB\QueryBuilder;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryException;
use Doctrine\DBAL\Result;
use OC\DB\QueryBuilder\Literal;
use OC\DB\QueryBuilder\Parameter;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Class QueryBuilderTest
 *
 * @group DB
 *
 * @package Test\DB\QueryBuilder
 */
class QueryBuilderTest extends \Test\TestCase {
	/** @var QueryBuilder */
	protected $queryBuilder;

	/** @var IDBConnection */
	protected $connection;

	/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = \OC::$server->getDatabaseConnection();
		$this->config = $this->createMock(SystemConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->queryBuilder = new QueryBuilder($this->connection, $this->config, $this->logger);
	}

	protected function createTestingRows($appId = 'testFirstResult') {
		$qB = $this->connection->getQueryBuilder();
		for ($i = 1; $i < 10; $i++) {
			$qB->insert('*PREFIX*appconfig')
				->values([
					'appid' => $qB->expr()->literal($appId),
					'configkey' => $qB->expr()->literal('testing' . $i),
					'configvalue' => $qB->expr()->literal(100 - $i),
				])
				->execute();
		}
	}

	protected function getTestingRows(QueryBuilder $queryBuilder) {
		$queryBuilder->select('configvalue')
			->from('*PREFIX*appconfig')
			->where($queryBuilder->expr()->eq(
				'appid',
				$queryBuilder->expr()->literal('testFirstResult')
			))
			->orderBy('configkey', 'ASC');

		$query = $queryBuilder->execute();
		$rows = [];
		while ($row = $query->fetch()) {
			$rows[] = $row['configvalue'];
		}
		$query->closeCursor();

		return $rows;
	}

	protected function deleteTestingRows($appId = 'testFirstResult') {
		$qB = $this->connection->getQueryBuilder();

		$qB->delete('*PREFIX*appconfig')
			->where($qB->expr()->eq('appid', $qB->expr()->literal($appId)))
			->execute();
	}

	public function dataFirstResult() {
		return [
			[0, [99, 98, 97, 96, 95, 94, 93, 92, 91]],
			[0, [99, 98, 97, 96, 95, 94, 93, 92, 91]],
			[1, [98, 97, 96, 95, 94, 93, 92, 91]],
			[5, [94, 93, 92, 91]],
		];
	}

	/**
	 * @dataProvider dataFirstResult
	 *
	 * @param int|null $firstResult
	 * @param array $expectedSet
	 */
	public function testFirstResult($firstResult, $expectedSet) {
		$this->deleteTestingRows();
		$this->createTestingRows();

		if ($firstResult !== null) {
			$this->queryBuilder->setFirstResult($firstResult);
		}

		$this->assertSame(
			$firstResult ?? 0,
			$this->queryBuilder->getFirstResult()
		);

		$rows = $this->getTestingRows($this->queryBuilder);

		$this->assertCount(sizeof($expectedSet), $rows);
		$this->assertEquals($expectedSet, $rows);

		$this->deleteTestingRows();
	}

	public function dataMaxResults() {
		return [
			[null, [99, 98, 97, 96, 95, 94, 93, 92, 91]],
			// Limit 0 gives mixed results: either all entries or none is returned
			//[0, []],
			[1, [99]],
			[5, [99, 98, 97, 96, 95]],
		];
	}

	/**
	 * @dataProvider dataMaxResults
	 *
	 * @param int $maxResult
	 * @param array $expectedSet
	 */
	public function testMaxResults($maxResult, $expectedSet) {
		$this->deleteTestingRows();
		$this->createTestingRows();

		if ($maxResult !== null) {
			$this->queryBuilder->setMaxResults($maxResult);
		}

		$this->assertSame(
			$maxResult,
			$this->queryBuilder->getMaxResults()
		);

		$rows = $this->getTestingRows($this->queryBuilder);

		$this->assertCount(sizeof($expectedSet), $rows);
		$this->assertEquals($expectedSet, $rows);

		$this->deleteTestingRows();
	}

	public function dataSelect() {
		$config = $this->createMock(SystemConfig::class);
		$logger = $this->createMock(LoggerInterface::class);
		$queryBuilder = new QueryBuilder(\OC::$server->getDatabaseConnection(), $config, $logger);
		return [
			// select('column1')
			[['configvalue'], ['configvalue' => '99']],

			// select('column1', 'column2')
			[['configvalue', 'configkey'], ['configvalue' => '99', 'configkey' => 'testing1']],

			// select(['column1', 'column2'])
			[[['configvalue', 'configkey']], ['configvalue' => '99', 'configkey' => 'testing1']],

			// select(new Literal('column1'))
			[[$queryBuilder->expr()->literal('column1')], [], 'column1'],

			// select('column1', 'column2')
			[[$queryBuilder->expr()->literal('column1'), 'configkey'], ['configkey' => 'testing1'], 'column1'],

			// select(['column1', 'column2'])
			[[[$queryBuilder->expr()->literal('column1'), 'configkey']], ['configkey' => 'testing1'], 'column1'],
		];
	}

	/**
	 * @dataProvider dataSelect
	 *
	 * @param array $selectArguments
	 * @param array $expected
	 * @param string $expectedLiteral
	 */
	public function testSelect($selectArguments, $expected, $expectedLiteral = '') {
		$this->deleteTestingRows();
		$this->createTestingRows();

		call_user_func_array(
			[$this->queryBuilder, 'select'],
			$selectArguments
		);

		$this->queryBuilder->from('*PREFIX*appconfig')
			->where($this->queryBuilder->expr()->eq(
				'appid',
				$this->queryBuilder->expr()->literal('testFirstResult')
			))
			->orderBy('configkey', 'ASC')
			->setMaxResults(1);

		$query = $this->queryBuilder->execute();
		$row = $query->fetch();
		$query->closeCursor();

		foreach ($expected as $key => $value) {
			$this->assertArrayHasKey($key, $row);
			$this->assertEquals($value, $row[$key]);
			unset($row[$key]);
		}

		if ($expectedLiteral) {
			$this->assertEquals([$expectedLiteral], array_values($row));
		} else {
			$this->assertEmpty($row);
		}

		$this->deleteTestingRows();
	}

	public function dataSelectAlias() {
		$config = $this->createMock(SystemConfig::class);
		$logger = $this->createMock(LoggerInterface::class);
		$queryBuilder = new QueryBuilder(\OC::$server->getDatabaseConnection(), $config, $logger);
		return [
			['configvalue', 'cv', ['cv' => '99']],
			[$queryBuilder->expr()->literal('column1'), 'thing', ['thing' => 'column1']],
		];
	}

	/**
	 * @dataProvider dataSelectAlias
	 *
	 * @param mixed $select
	 * @param array $alias
	 * @param array $expected
	 */
	public function testSelectAlias($select, $alias, $expected) {
		$this->deleteTestingRows();
		$this->createTestingRows();

		$this->queryBuilder->selectAlias($select, $alias);

		$this->queryBuilder->from('*PREFIX*appconfig')
			->where($this->queryBuilder->expr()->eq(
				'appid',
				$this->queryBuilder->expr()->literal('testFirstResult')
			))
			->orderBy('configkey', 'ASC')
			->setMaxResults(1);

		$query = $this->queryBuilder->execute();
		$row = $query->fetch();
		$query->closeCursor();

		$this->assertEquals(
			$expected,
			$row
		);

		$this->deleteTestingRows();
	}

	public function testSelectDistinct() {
		$this->deleteTestingRows('testFirstResult1');
		$this->deleteTestingRows('testFirstResult2');
		$this->createTestingRows('testFirstResult1');
		$this->createTestingRows('testFirstResult2');

		$this->queryBuilder->selectDistinct('appid');

		$this->queryBuilder->from('*PREFIX*appconfig')
			->where($this->queryBuilder->expr()->in(
				'appid',
				[$this->queryBuilder->expr()->literal('testFirstResult1'), $this->queryBuilder->expr()->literal('testFirstResult2')]
			))
			->orderBy('appid', 'DESC');

		$query = $this->queryBuilder->execute();
		$rows = $query->fetchAll();
		$query->closeCursor();

		$this->assertEquals(
			[['appid' => 'testFirstResult2'], ['appid' => 'testFirstResult1']],
			$rows
		);

		$this->deleteTestingRows('testFirstResult1');
		$this->deleteTestingRows('testFirstResult2');
	}

	public function testSelectDistinctMultiple() {
		$this->deleteTestingRows('testFirstResult1');
		$this->deleteTestingRows('testFirstResult2');
		$this->createTestingRows('testFirstResult1');
		$this->createTestingRows('testFirstResult2');

		$this->queryBuilder->selectDistinct(['appid', 'configkey']);

		$this->queryBuilder->from('*PREFIX*appconfig')
			->where($this->queryBuilder->expr()->eq(
				'appid',
				$this->queryBuilder->expr()->literal('testFirstResult1')
			))
			->orderBy('configkey', 'ASC');

		$query = $this->queryBuilder->execute();
		$rows = $query->fetchAll();
		$query->closeCursor();

		$this->assertEquals(
			[
				['appid' => 'testFirstResult1', 'configkey' => 'testing1'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing2'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing3'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing4'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing5'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing6'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing7'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing8'],
				['appid' => 'testFirstResult1', 'configkey' => 'testing9'],
			],
			$rows
		);

		$this->deleteTestingRows('testFirstResult1');
		$this->deleteTestingRows('testFirstResult2');
	}

	public function dataAddSelect() {
		$config = $this->createMock(SystemConfig::class);
		$logger = $this->createMock(LoggerInterface::class);
		$queryBuilder = new QueryBuilder(\OC::$server->getDatabaseConnection(), $config, $logger);
		return [
			// addSelect('column1')
			[['configvalue'], ['appid' => 'testFirstResult', 'configvalue' => '99']],

			// addSelect('column1', 'column2')
			[['configvalue', 'configkey'], ['appid' => 'testFirstResult', 'configvalue' => '99', 'configkey' => 'testing1']],

			// addSelect(['column1', 'column2'])
			[[['configvalue', 'configkey']], ['appid' => 'testFirstResult', 'configvalue' => '99', 'configkey' => 'testing1']],

			// select(new Literal('column1'))
			[[$queryBuilder->expr()->literal('column1')], ['appid' => 'testFirstResult'], 'column1'],

			// select('column1', 'column2')
			[[$queryBuilder->expr()->literal('column1'), 'configkey'], ['appid' => 'testFirstResult', 'configkey' => 'testing1'], 'column1'],

			// select(['column1', 'column2'])
			[[[$queryBuilder->expr()->literal('column1'), 'configkey']], ['appid' => 'testFirstResult', 'configkey' => 'testing1'], 'column1'],
		];
	}

	/**
	 * @dataProvider dataAddSelect
	 *
	 * @param array $selectArguments
	 * @param array $expected
	 * @param string $expectedLiteral
	 */
	public function testAddSelect($selectArguments, $expected, $expectedLiteral = '') {
		$this->deleteTestingRows();
		$this->createTestingRows();

		$this->queryBuilder->select('appid');

		call_user_func_array(
			[$this->queryBuilder, 'addSelect'],
			$selectArguments
		);

		$this->queryBuilder->from('*PREFIX*appconfig')
			->where($this->queryBuilder->expr()->eq(
				'appid',
				$this->queryBuilder->expr()->literal('testFirstResult')
			))
			->orderBy('configkey', 'ASC')
			->setMaxResults(1);

		$query = $this->queryBuilder->execute();
		$row = $query->fetch();
		$query->closeCursor();

		foreach ($expected as $key => $value) {
			$this->assertArrayHasKey($key, $row);
			$this->assertEquals($value, $row[$key]);
			unset($row[$key]);
		}

		if ($expectedLiteral) {
			$this->assertEquals([$expectedLiteral], array_values($row));
		} else {
			$this->assertEmpty($row);
		}

		$this->deleteTestingRows();
	}

	public function dataDelete(): array {
		return [
			['data', null, '`*PREFIX*data`'],
			['data', 't', '`*PREFIX*data`'],
		];
	}

	/**
	 * @dataProvider dataDelete
	 */
	public function testDelete(string $tableName, ?string $tableAlias, string $expectedQuery): void {
		$this->queryBuilder->delete($tableName, $tableAlias);

		$this->assertSame(
			'DELETE FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataUpdate(): array {
		return [
			['data', null, '`*PREFIX*data`'],
			['data', 't',  '`*PREFIX*data`'],
		];
	}

	/**
	 * @dataProvider dataUpdate
	 */
	public function testUpdate(string $tableName, ?string $tableAlias, string $expectedQuery): void {
		$this->queryBuilder->update($tableName, $tableAlias);

		$this->assertSame(
			'UPDATE ' . $expectedQuery . ' SET ',
			$this->queryBuilder->getSQL()
		);
	}

	public function dataInsert(): array {
		return [
			['data', '`*PREFIX*data`'],
		];
	}

	/**
	 * @dataProvider dataInsert
	 */
	public function testInsert(string $tableName, string $expectedQuery): void {
		$this->queryBuilder->insert($tableName);

		$this->assertSame(
			'INSERT INTO ' . $expectedQuery . ' () VALUES()',
			$this->queryBuilder->getSQL()
		);
	}

	public function dataFrom(): array {
		$config = $this->createMock(SystemConfig::class);
		$logger = $this->createMock(LoggerInterface::class);
		$qb = new QueryBuilder(\OC::$server->getDatabaseConnection(), $config, $logger);
		return [
			[$qb->createFunction('(' . $qb->select('*')->from('test')->getSQL() . ')'), 'q', null, null, '(SELECT * FROM `*PREFIX*test`) `q`'],
			['data', null, null, null, '`*PREFIX*data`'],
			['data', 't', null, null, '`*PREFIX*data` `t`'],
			['data1', null, 'data2', null, '`*PREFIX*data1`, `*PREFIX*data2`'],
			['data', 't1', 'data', 't2', '`*PREFIX*data` `t1`, `*PREFIX*data` `t2`'],
		];
	}

	/**
	 * @dataProvider dataFrom
	 */
	public function testFrom(string|IQueryFunction $table1Name, ?string $table1Alias, null|string|IQueryFunction $table2Name, ?string $table2Alias, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->from($table1Name, $table1Alias);
		if ($table2Name !== null) {
			$this->queryBuilder->from($table2Name, $table2Alias);
		}

		$this->assertSame(
			'SELECT * FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataJoin(): array {
		return [
			[
				'd1', 'data2', 'd2', null,
				'`*PREFIX*data1` `d1` INNER JOIN `*PREFIX*data2` `d2`'
			],
			[
				'd1', 'data2', 'd2', '`d1`.`field1` = `d2`.`field2`',
				'`*PREFIX*data1` `d1` INNER JOIN `*PREFIX*data2` `d2` ON `d1`.`field1` = `d2`.`field2`'
			],

		];
	}

	/**
	 * @dataProvider dataJoin
	 */
	public function testJoin(string $fromAlias, string $tableName, string $tableAlias, ?string $condition, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->join(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);


		$this->assertSame(
			'SELECT * FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	/**
	 * @dataProvider dataJoin
	 */
	public function testInnerJoin(string $fromAlias, string $tableName, string $tableAlias, ?string $condition, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->innerJoin(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);

		$this->assertSame(
			'SELECT * FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataLeftJoin(): array {
		return [
			[
				'd1', 'data2', 'd2', null,
				'`*PREFIX*data1` `d1` LEFT JOIN `*PREFIX*data2` `d2`'
			],
			[
				'd1', 'data2', 'd2', '`d1`.`field1` = `d2`.`field2`',
				'`*PREFIX*data1` `d1` LEFT JOIN `*PREFIX*data2` `d2` ON `d1`.`field1` = `d2`.`field2`'
			],
		];
	}

	/**
	 * @dataProvider dataLeftJoin
	 */
	public function testLeftJoin(string $fromAlias, string $tableName, string $tableAlias, ?string $condition, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->leftJoin(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);

		$this->assertSame(
			'SELECT * FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataRightJoin(): array {
		return [
			[
				'd1', 'data2', 'd2', null,
				'`*PREFIX*data1` `d1` RIGHT JOIN `*PREFIX*data2` `d2`'
			],
			[
				'd1', 'data2', 'd2', '`d1`.`field1` = `d2`.`field2`',
				'`*PREFIX*data1` `d1` RIGHT JOIN `*PREFIX*data2` `d2` ON `d1`.`field1` = `d2`.`field2`'
			],
		];
	}

	/**
	 * @dataProvider dataRightJoin
	 */
	public function testRightJoin(string $fromAlias, string $tableName, string $tableAlias, ?string $condition, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->rightJoin(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);

		$this->assertSame(
			'SELECT * FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataSet(): array {
		return [
			['column1', new Literal('value'), null, null, '`column1` = value'],
			['column1', new Parameter(':param'), null, null, '`column1` = :param'],
			['column1', 'column2', null, null, '`column1` = `column2`'],
			['column1', 'column2', 'column3', new Literal('value'), '`column1` = `column2`, `column3` = value'],
		];
	}

	/**
	 * @dataProvider dataSet
	 */
	public function testSet(string $partOne1, string|Literal|Parameter $partOne2, ?string $partTwo1, null|string|Literal|Parameter $partTwo2, string $expectedQuery): void {
		$this->queryBuilder->update('data');
		$this->queryBuilder->set($partOne1, $partOne2);
		if ($partTwo1 !== null) {
			$this->queryBuilder->set($partTwo1, $partTwo2);
		}

		$this->assertSame(
			'UPDATE `*PREFIX*data` SET ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataWhere(): array {
		return [
			[['where1'], 'where1'],
			[['where1', 'where2'], '(where1) AND (where2)'],
		];
	}

	/**
	 * @dataProvider dataWhere
	 */
	public function testWhere(array $whereArguments, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'where'],
			$whereArguments
		);

		$this->assertSame(
			'SELECT `column` WHERE ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	/**
	 * @dataProvider dataWhere
	 *
	 * @param array $whereArguments
	 * @param array $expectedQueryPart
	 * @param string $expectedQuery
	 */
	public function testAndWhere(array $whereArguments, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'andWhere'],
			$whereArguments
		);

		$this->assertSame(
			'SELECT `column` WHERE ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataOrWhere(): array {
		return [
			[['where1'],  'where1'],
			[['where1', 'where2'], '(where1) OR (where2)'],
		];
	}

	/**
	 * @dataProvider dataOrWhere
	 */
	public function testOrWhere(array $whereArguments, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'orWhere'],
			$whereArguments
		);

		$this->assertSame(
			'SELECT `column` WHERE ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataGroupBy(): array {
		return [
			[['column1'], '`column1`'],
			[['column1', 'column2'], '`column1`, `column2`'],
		];
	}

	/**
	 * @dataProvider dataGroupBy
	 */
	public function testGroupBy(array $groupByArguments, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'groupBy'],
			$groupByArguments
		);
		$this->assertSame(
			'SELECT `column` GROUP BY ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataAddGroupBy(): array {
		return [
			[['column2'], '`column1`, `column2`'],
			[['column2', 'column3'], '`column1`, `column2`, `column3`'],
		];
	}

	/**
	 * @dataProvider dataAddGroupBy
	 */
	public function testAddGroupBy(array $groupByArguments, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		$this->queryBuilder->groupBy('column1');
		call_user_func_array(
			[$this->queryBuilder, 'addGroupBy'],
			$groupByArguments
		);

		$this->assertSame(
			'SELECT `column` GROUP BY ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataSetValue(): array {
		return [
			['column', 'value', '(`column`) VALUES(value)'],
		];
	}

	/**
	 * @dataProvider dataSetValue
	 */
	public function testSetValue(string $column, string $value, string $expectedQuery): void {
		$this->queryBuilder->insert('data');
		$this->queryBuilder->setValue($column, $value);

		$this->assertSame(
			'INSERT INTO `*PREFIX*data` ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	/**
	 * @dataProvider dataSetValue
	 */
	public function testValues(string $column, string $value, string $expectedQuery): void {
		$this->queryBuilder->insert('data');
		$this->queryBuilder->values([
			$column => $value,
		]);

		$this->assertSame(
			'INSERT INTO `*PREFIX*data` ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataHaving(): array {
		return [
			[['condition1'], 'HAVING condition1'],
			[['condition1', 'condition2'], 'HAVING (condition1) AND (condition2)'],
			[
				[new CompositeExpression('OR', 'condition1', 'condition2')],
				'HAVING (condition1) OR (condition2)'
			],
			[
				[new CompositeExpression('AND', 'condition1', 'condition2')],
				'HAVING (condition1) AND (condition2)'
			],
		];
	}

	/**
	 * @dataProvider dataHaving
	 */
	public function testHaving(array $havingArguments, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		call_user_func_array(
			[$this->queryBuilder, 'having'],
			$havingArguments
		);

		$this->assertSame(
			'SELECT * ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataAndHaving(): array {
		return [
			[['condition2'], 'HAVING (condition1) AND (condition2)'],
			[['condition2', 'condition3'], 'HAVING (condition1) AND (condition2) AND (condition3)'],
			[
				[new CompositeExpression('OR', 'condition2', 'condition3')],
				'HAVING (condition1) AND ((condition2) OR (condition3))'
			],
			[
				[new CompositeExpression('AND', 'condition2', 'condition3')],
				'HAVING (condition1) AND ((condition2) AND (condition3))'
			],
		];
	}

	/**
	 * @dataProvider dataAndHaving
	 */
	public function testAndHaving(array $havingArguments, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->having('condition1');
		call_user_func_array(
			[$this->queryBuilder, 'andHaving'],
			$havingArguments
		);

		$this->assertSame(
			'SELECT * ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataOrHaving(): array {
		return [
			[['condition2'], 'HAVING (condition1) OR (condition2)'],
			[['condition2', 'condition3'], 'HAVING (condition1) OR (condition2) OR (condition3)'],
			[
				[new CompositeExpression('OR', 'condition2', 'condition3')],
				'HAVING (condition1) OR ((condition2) OR (condition3))'
			],
			[
				[new CompositeExpression('AND', 'condition2', 'condition3')],
				'HAVING (condition1) OR ((condition2) AND (condition3))'
			],
		];
	}

	/**
	 * @dataProvider dataOrHaving
	 */
	public function testOrHaving(array $havingArguments, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->having('condition1');
		call_user_func_array(
			[$this->queryBuilder, 'orHaving'],
			$havingArguments
		);

		$this->assertSame(
			'SELECT * ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataOrderBy(): array {
		return [
			['column', null, 'ORDER BY `column` ASC'],
			['column', 'ASC', 'ORDER BY `column` ASC'],
			['column', 'DESC', 'ORDER BY `column` DESC'],
		];
	}

	/**
	 * @dataProvider dataOrderBy
	 */
	public function testOrderBy(string $sort, ?string $order, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->orderBy($sort, $order);

		$this->assertSame(
			'SELECT * ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function dataAddOrderBy(): array {
		return [
			['column2', null, null, 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', null, 'ASC', 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', null, 'DESC', 'ORDER BY `column1` DESC, `column2` ASC'],
			['column2', 'ASC', null, 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', 'ASC', 'ASC', 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', 'ASC', 'DESC', 'ORDER BY `column1` DESC, `column2` ASC'],
			['column2', 'DESC', null, 'ORDER BY `column1` ASC, `column2` DESC'],
			['column2', 'DESC', 'ASC', 'ORDER BY `column1` ASC, `column2` DESC'],
			['column2', 'DESC', 'DESC', 'ORDER BY `column1` DESC, `column2` DESC'],
		];
	}

	/**
	 * @dataProvider dataAddOrderBy
	 */
	public function testAddOrderBy(string $sort2, ?string $order2, ?string $order1, string $expectedQuery): void {
		$this->queryBuilder->select('*');
		$this->queryBuilder->orderBy('column1', $order1);
		$this->queryBuilder->addOrderBy($sort2, $order2);

		$this->assertSame(
			'SELECT * ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function testGetLastInsertId() {
		$qB = $this->connection->getQueryBuilder();

		try {
			$qB->getLastInsertId();
			$this->fail('getLastInsertId() should throw an exception, when being called before insert()');
		} catch (\BadMethodCallException $e) {
			$this->addToAssertionCount(1);
		}

		$qB->insert('properties')
			->values([
				'userid' => $qB->expr()->literal('testFirstResult'),
				'propertypath' => $qB->expr()->literal('testing'),
				'propertyname' => $qB->expr()->literal('testing'),
				'propertyvalue' => $qB->expr()->literal('testing'),
			])
			->execute();

		$actual = $qB->getLastInsertId();

		$this->assertNotNull($actual);
		$this->assertIsInt($actual);
		$this->assertEquals($this->connection->lastInsertId('*PREFIX*properties'), $actual);

		$qB->delete('properties')
			->where($qB->expr()->eq('userid', $qB->expr()->literal('testFirstResult')))
			->execute();

		try {
			$qB->getLastInsertId();
			$this->fail('getLastInsertId() should throw an exception, when being called after delete()');
		} catch (\BadMethodCallException $e) {
			$this->addToAssertionCount(1);
		}
	}

	public function dataGetTableName() {
		$config = $this->createMock(SystemConfig::class);
		$logger = $this->createMock(LoggerInterface::class);
		$qb = new QueryBuilder(\OC::$server->getDatabaseConnection(), $config, $logger);
		return [
			['*PREFIX*table', null, '`*PREFIX*table`'],
			['*PREFIX*table', true, '`*PREFIX*table`'],
			['*PREFIX*table', false, '`*PREFIX*table`'],

			['table', null, '`*PREFIX*table`'],
			['table', true, '`*PREFIX*table`'],
			['table', false, '`table`'],

			[$qb->createFunction('(' . $qb->select('*')->from('table')->getSQL() . ')'), null, '(SELECT * FROM `*PREFIX*table`)'],
			[$qb->createFunction('(' . $qb->select('*')->from('table')->getSQL() . ')'), true, '(SELECT * FROM `*PREFIX*table`)'],
			[$qb->createFunction('(' . $qb->select('*')->from('table')->getSQL() . ')'), false, '(SELECT * FROM `*PREFIX*table`)'],
		];
	}

	/**
	 * @dataProvider dataGetTableName
	 *
	 * @param string|IQueryFunction $tableName
	 * @param bool $automatic
	 * @param string $expected
	 */
	public function testGetTableName($tableName, $automatic, $expected) {
		if ($automatic !== null) {
			$this->queryBuilder->automaticTablePrefix($automatic);
		}

		$this->assertSame(
			$expected,
			$this->queryBuilder->getTableName($tableName)
		);
	}

	public function dataGetColumnName() {
		return [
			['column', '', '`column`'],
			['column', 'a', '`a`.`column`'],
		];
	}

	/**
	 * @dataProvider dataGetColumnName
	 * @param string $column
	 * @param string $prefix
	 * @param string $expected
	 */
	public function testGetColumnName($column, $prefix, $expected) {
		$this->assertSame(
			$expected,
			$this->queryBuilder->getColumnName($column, $prefix)
		);
	}

	public function testExecuteWithoutLogger(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->expects($this->once())
			->method('executeStatement')
			->willReturn(3);
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn([]);
		$this->logger
			->expects($this->never())
			->method('debug');
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(false);

		$this->queryBuilder->insert('migrations');
		self::invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->assertEquals(3, $this->queryBuilder->execute());
	}

	public function testExecuteWithLoggerAndNamedArray(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn([
				'foo' => 'bar',
				'key' => 'value',
			]);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('SELECT * FROM FOO WHERE BAR = ?');
		$queryBuilder
			->expects($this->once())
			->method('executeStatement')
			->willReturn(3);
		$this->logger
			->expects($this->once())
			->method('debug')
			->with(
				'DB QueryBuilder: \'{query}\' with parameters: {params}',
				[
					'query' => 'SELECT * FROM FOO WHERE BAR = ?',
					'params' => 'foo => \'bar\', key => \'value\'',
					'app' => 'core',
				]
			);
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(true);

		$this->queryBuilder->insert('migrations');
		self::invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->assertEquals(3, $this->queryBuilder->execute());
	}

	public function testExecuteWithLoggerAndUnnamedArray(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn(['Bar']);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('SELECT * FROM FOO WHERE BAR = ?');
		$queryBuilder
			->expects($this->once())
			->method('executeStatement')
			->willReturn(3);
		$this->logger
			->expects($this->once())
			->method('debug')
			->with(
				'DB QueryBuilder: \'{query}\' with parameters: {params}',
				[
					'query' => 'SELECT * FROM FOO WHERE BAR = ?',
					'params' => '0 => \'Bar\'',
					'app' => 'core',
				]
			);
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(true);

		$this->queryBuilder->insert('migrations');
		self::invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->assertEquals(3, $this->queryBuilder->execute());
	}

	public function testExecuteWithLoggerAndNoParams(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn([]);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('SELECT * FROM FOO WHERE BAR = ?');
		$queryBuilder
			->expects($this->once())
			->method('executeStatement')
			->willReturn(3);
		$this->logger
			->expects($this->once())
			->method('debug')
			->with(
				'DB QueryBuilder: \'{query}\'',
				[
					'query' => 'SELECT * FROM FOO WHERE BAR = ?',
					'app' => 'core',
				]
			);
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(true);

		$this->queryBuilder->insert('migrations');
		self::invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->assertEquals(3, $this->queryBuilder->execute());
	}

	public function testExecuteWithParameterTooLarge(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$p = array_fill(0, 1001, 'foo');
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn([$p]);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('SELECT * FROM FOO WHERE BAR IN (?)');
		$queryBuilder
			->expects($this->once())
			->method('executeQuery')
			->willReturn($this->createMock(Result::class));
		$this->logger
			->expects($this->once())
			->method('error')
			->willReturnCallback(function ($message, $parameters) {
				$this->assertInstanceOf(QueryException::class, $parameters['exception']);
				$this->assertSame(
					'More than 1000 expressions in a list are not allowed on Oracle.',
					$message
				);
			});
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(false);

		self::invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->queryBuilder->execute();
	}

	public function testExecuteWithParametersTooMany(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$p = array_fill(0, 999, 'foo');
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn(array_fill(0, 66, $p));
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('SELECT * FROM FOO WHERE BAR IN (?) OR BAR IN (?)');
		$queryBuilder
			->expects($this->once())
			->method('executeQuery')
			->willReturn($this->createMock(Result::class));
		$this->logger
			->expects($this->once())
			->method('error')
			->willReturnCallback(function ($message, $parameters) {
				$this->assertInstanceOf(QueryException::class, $parameters['exception']);
				$this->assertSame(
					'The number of parameters must not exceed 65535. Restriction by PostgreSQL.',
					$message
				);
			});
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(false);

		self::invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->queryBuilder->execute();
	}
}
