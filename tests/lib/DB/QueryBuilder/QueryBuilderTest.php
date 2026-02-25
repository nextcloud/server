<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\DB\QueryBuilder;

use BadMethodCallException;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryException;
use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\Literal;
use OC\DB\QueryBuilder\Parameter;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\ILiteral;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function str_starts_with;

#[Group(name: 'DB')]
class QueryBuilderTest extends \Test\TestCase {
	private SystemConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;

	private QueryBuilder $queryBuilder;
	private IDBConnection $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->config = $this->createMock(SystemConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->queryBuilder = new QueryBuilder($this->connection, $this->config, $this->logger);
	}

	/**
	 * @psalm-param 'testFirstResult'|'testFirstResult1'|'testFirstResult2' $appId
	 */
	protected function createTestingRows(string $appId = 'testFirstResult'): void {
		$qB = $this->connection->getQueryBuilder();
		for ($i = 1; $i < 10; $i++) {
			$qB->insert('*PREFIX*appconfig')
				->values([
					'appid' => $qB->expr()->literal($appId),
					'configkey' => $qB->expr()->literal('testing' . $i),
					'configvalue' => $qB->expr()->literal(100 - $i),
				])
				->executeStatement();
		}
	}

	protected function getTestingRows(QueryBuilder $queryBuilder): array {
		$queryBuilder->select('configvalue')
			->from('*PREFIX*appconfig')
			->where($queryBuilder->expr()->eq(
				'appid',
				$queryBuilder->expr()->literal('testFirstResult')
			))
			->orderBy('configkey', 'ASC');

		$query = $queryBuilder->executeQuery();
		$rows = [];
		while ($row = $query->fetch()) {
			$rows[] = $row['configvalue'];
		}
		$query->closeCursor();

		return $rows;
	}

	/**
	 * @psalm-param 'testFirstResult'|'testFirstResult1'|'testFirstResult2' $appId
	 */
	protected function deleteTestingRows(string $appId = 'testFirstResult'): void {
		$qB = $this->connection->getQueryBuilder();

		$qB->delete('*PREFIX*appconfig')
			->where($qB->expr()->eq('appid', $qB->expr()->literal($appId)))
			->executeStatement();
	}

	public static function dataFirstResult(): array {
		return [
			[0, [99, 98, 97, 96, 95, 94, 93, 92, 91]],
			[0, [99, 98, 97, 96, 95, 94, 93, 92, 91]],
			[1, [98, 97, 96, 95, 94, 93, 92, 91]],
			[5, [94, 93, 92, 91]],
		];
	}

	#[DataProvider(methodName: 'dataFirstResult')]
	public function testFirstResult(?int $firstResult, array $expectedSet): void {
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

	public static function dataMaxResults(): array {
		return [
			[null, [99, 98, 97, 96, 95, 94, 93, 92, 91]],
			// Limit 0 gives mixed results: either all entries or none is returned
			//[0, []],
			[1, [99]],
			[5, [99, 98, 97, 96, 95]],
		];
	}

	#[DataProvider(methodName: 'dataMaxResults')]
	public function testMaxResults(?int $maxResult, array $expectedSet): void {
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

	public static function dataSelect(): array {
		return [
			// select('column1')
			[['configvalue'], ['configvalue' => '99']],

			// select('column1', 'column2')
			[['configvalue', 'configkey'], ['configvalue' => '99', 'configkey' => 'testing1']],

			// select(['column1', 'column2'])
			[[['configvalue', 'configkey']], ['configvalue' => '99', 'configkey' => 'testing1']],

			// select(new Literal('column1'))
			[['l::column1'], [], 'column1'],

			// select(new Literal('column1'), 'column2')
			[['l::column1', 'configkey'], ['configkey' => 'testing1'], 'column1'],

			// select([new Literal('column1'), 'column2'])
			[[['l::column1', 'configkey']], ['configkey' => 'testing1'], 'column1'],
		];
	}

	#[DataProvider(methodName: 'dataSelect')]
	public function testSelect(array $selectArguments, array $expected, string $expectedLiteral = ''): void {
		$this->deleteTestingRows();
		$this->createTestingRows();

		array_walk_recursive(
			$selectArguments,
			function (string &$arg): void {
				if (str_starts_with($arg, 'l::')) {
					$arg = $this->queryBuilder->expr()->literal(substr($arg, 3));
				}
			},
		);
		$this->queryBuilder->select(...$selectArguments);

		$this->queryBuilder->from('*PREFIX*appconfig')
			->where($this->queryBuilder->expr()->eq(
				'appid',
				$this->queryBuilder->expr()->literal('testFirstResult')
			))
			->orderBy('configkey', 'ASC')
			->setMaxResults(1);

		$query = $this->queryBuilder->executeQuery();
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

	public static function dataSelectAlias(): array {
		return [
			['configvalue', 'cv', ['cv' => '99']],
			['l::column1', 'thing', ['thing' => 'column1']],
		];
	}

	#[DataProvider(methodName: 'dataSelectAlias')]
	public function testSelectAlias(string $select, string $alias, array $expected): void {
		if (str_starts_with($select, 'l::')) {
			$select = $this->queryBuilder->expr()->literal(substr($select, 3));
		}

		$this->deleteTestingRows();
		$this->createTestingRows();

		$this->queryBuilder->selectAlias($select, $alias);

		$this->queryBuilder->from('appconfig')
			->where($this->queryBuilder->expr()->eq(
				'appid',
				$this->queryBuilder->expr()->literal('testFirstResult')
			))
			->orderBy('configkey', 'ASC')
			->setMaxResults(1);

		$query = $this->queryBuilder->executeQuery();
		$row = $query->fetch();
		$query->closeCursor();

		$this->assertEquals(
			$expected,
			$row
		);

		$this->deleteTestingRows();
	}

	public function testSelectDistinct(): void {
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

		$query = $this->queryBuilder->executeQuery();
		$rows = $query->fetchAll();
		$query->closeCursor();

		$this->assertEquals(
			[['appid' => 'testFirstResult2'], ['appid' => 'testFirstResult1']],
			$rows
		);

		$this->deleteTestingRows('testFirstResult1');
		$this->deleteTestingRows('testFirstResult2');
	}

	public function testSelectDistinctMultiple(): void {
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

		$query = $this->queryBuilder->executeQuery();
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

	public static function dataAddSelect(): array {
		return [
			// addSelect('column1')
			[['configvalue'], ['appid' => 'testFirstResult', 'configvalue' => '99']],

			// addSelect('column1', 'column2')
			[['configvalue', 'configkey'], ['appid' => 'testFirstResult', 'configvalue' => '99', 'configkey' => 'testing1']],

			// addSelect(['column1', 'column2'])
			[[['configvalue', 'configkey']], ['appid' => 'testFirstResult', 'configvalue' => '99', 'configkey' => 'testing1']],

			// select(new Literal('column1'))
			[['l::column1'], ['appid' => 'testFirstResult'], 'column1'],

			// select(new Literal('column1'), 'column2')
			[['l::column1', 'configkey'], ['appid' => 'testFirstResult', 'configkey' => 'testing1'], 'column1'],

			// select([new Literal('column1'), 'column2'])
			[[['l::column1', 'configkey']], ['appid' => 'testFirstResult', 'configkey' => 'testing1'], 'column1'],
		];
	}

	#[DataProvider(methodName: 'dataAddSelect')]
	public function testAddSelect(array $selectArguments, array $expected, string $expectedLiteral = ''): void {
		$this->deleteTestingRows();
		$this->createTestingRows();

		array_walk_recursive(
			$selectArguments,
			function (string &$arg): void {
				if (str_starts_with($arg, 'l::')) {
					$arg = $this->queryBuilder->expr()->literal(substr($arg, 3));
				}
			},
		);

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

		$query = $this->queryBuilder->executeQuery();
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

	public static function dataDelete(): array {
		return [
			['data', null, ['table' => '`*PREFIX*data`', 'alias' => null], '`*PREFIX*data`'],
			['data', 't', ['table' => '`*PREFIX*data`', 'alias' => 't'], '`*PREFIX*data` t'],
		];
	}

	#[DataProvider(methodName: 'dataDelete')]
	public function testDelete(string $tableName, ?string $tableAlias, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->delete($tableName, $tableAlias);

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('from')
		);

		$this->assertSame(
			'DELETE FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataUpdate(): array {
		return [
			['data', null, ['table' => '`*PREFIX*data`', 'alias' => null], '`*PREFIX*data`'],
			['data', 't', ['table' => '`*PREFIX*data`', 'alias' => 't'], '`*PREFIX*data` t'],
		];
	}

	#[DataProvider(methodName: 'dataUpdate')]
	public function testUpdate(string $tableName, ?string $tableAlias, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->update($tableName, $tableAlias);

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('from')
		);

		$this->assertSame(
			'UPDATE ' . $expectedQuery . ' SET ',
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataInsert(): array {
		return [
			['data', ['table' => '`*PREFIX*data`'], '`*PREFIX*data`'],
		];
	}

	#[DataProvider(methodName: 'dataInsert')]
	public function testInsert(string $tableName, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->insert($tableName);

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('from')
		);

		$this->assertSame(
			'INSERT INTO ' . $expectedQuery . ' () VALUES()',
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataFrom(): array {
		return [
			['function', 'q', null, null, [
				['table' => '(SELECT * FROM `*PREFIX*test`)', 'alias' => '`q`']
			], '(SELECT * FROM `*PREFIX*test`) `q`'],
			['data', null, null, null, [['table' => '`*PREFIX*data`', 'alias' => null]], '`*PREFIX*data`'],
			['data', 't', null, null, [['table' => '`*PREFIX*data`', 'alias' => '`t`']], '`*PREFIX*data` `t`'],
			['data1', null, 'data2', null, [
				['table' => '`*PREFIX*data1`', 'alias' => null],
				['table' => '`*PREFIX*data2`', 'alias' => null]
			], '`*PREFIX*data1`, `*PREFIX*data2`'],
			['data', 't1', 'data', 't2', [
				['table' => '`*PREFIX*data`', 'alias' => '`t1`'],
				['table' => '`*PREFIX*data`', 'alias' => '`t2`']
			], '`*PREFIX*data` `t1`, `*PREFIX*data` `t2`'],
		];
	}

	#[DataProvider(methodName: 'dataFrom')]
	public function testFrom(string $table1Name, ?string $table1Alias, ?string $table2Name, ?string $table2Alias, array $expectedQueryPart, string $expectedQuery): void {
		$config = $this->createMock(SystemConfig::class);
		$logger = $this->createMock(LoggerInterface::class);
		$queryBuilder = new QueryBuilder(Server::get(IDBConnection::class), $config, $logger);

		if ($table1Name === 'function') {
			$table1Name = $queryBuilder->createFunction('(' . $queryBuilder->select('*')->from('test')->getSQL() . ')');
		}
		$this->queryBuilder->from($table1Name, $table1Alias);
		if ($table2Name !== null) {
			$this->queryBuilder->from($table2Name, $table2Alias);
		}

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('from')
		);

		$this->assertSame(
			'SELECT  FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataJoin(): array {
		return [
			[
				'd1', 'data2', null, null,
				['`d1`' => [['joinType' => 'inner', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => null, 'joinCondition' => null]]],
				'`*PREFIX*data1` `d1` INNER JOIN `*PREFIX*data2` '
			],
			[
				'd1', 'data2', 'd2', null,
				['`d1`' => [['joinType' => 'inner', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => '`d2`', 'joinCondition' => null]]],
				'`*PREFIX*data1` `d1` INNER JOIN `*PREFIX*data2` `d2`'
			],
			[
				'd1', 'data2', 'd2', '`d1`.`field1` = `d2`.`field2`',
				['`d1`' => [['joinType' => 'inner', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => '`d2`', 'joinCondition' => '`d1`.`field1` = `d2`.`field2`']]],
				'`*PREFIX*data1` `d1` INNER JOIN `*PREFIX*data2` `d2` ON `d1`.`field1` = `d2`.`field2`'
			],

		];
	}

	#[DataProvider(methodName: 'dataJoin')]
	public function testJoin(string $fromAlias, string $tableName, ?string $tableAlias, ?string $condition, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->join(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('join')
		);

		$this->assertSame(
			'SELECT  FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	#[DataProvider(methodName: 'dataJoin')]
	public function testInnerJoin(string $fromAlias, string $tableName, ?string $tableAlias, ?string $condition, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->innerJoin(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('join')
		);

		$this->assertSame(
			'SELECT  FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataLeftJoin(): array {
		return [
			[
				'd1', 'data2', null, null,
				['`d1`' => [['joinType' => 'left', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => null, 'joinCondition' => null]]],
				'`*PREFIX*data1` `d1` LEFT JOIN `*PREFIX*data2` '
			],
			[
				'd1', 'data2', 'd2', null,
				['`d1`' => [['joinType' => 'left', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => '`d2`', 'joinCondition' => null]]],
				'`*PREFIX*data1` `d1` LEFT JOIN `*PREFIX*data2` `d2`'
			],
			[
				'd1', 'data2', 'd2', '`d1`.`field1` = `d2`.`field2`',
				['`d1`' => [['joinType' => 'left', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => '`d2`', 'joinCondition' => '`d1`.`field1` = `d2`.`field2`']]],
				'`*PREFIX*data1` `d1` LEFT JOIN `*PREFIX*data2` `d2` ON `d1`.`field1` = `d2`.`field2`'
			],
		];
	}

	#[DataProvider(methodName: 'dataLeftJoin')]
	public function testLeftJoin(string $fromAlias, string $tableName, ?string $tableAlias, ?string $condition, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->leftJoin(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('join')
		);

		$this->assertSame(
			'SELECT  FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataRightJoin(): array {
		return [
			[
				'd1', 'data2', null, null,
				['`d1`' => [['joinType' => 'right', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => null, 'joinCondition' => null]]],
				'`*PREFIX*data1` `d1` RIGHT JOIN `*PREFIX*data2` '
			],
			[
				'd1', 'data2', 'd2', null,
				['`d1`' => [['joinType' => 'right', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => '`d2`', 'joinCondition' => null]]],
				'`*PREFIX*data1` `d1` RIGHT JOIN `*PREFIX*data2` `d2`'
			],
			[
				'd1', 'data2', 'd2', '`d1`.`field1` = `d2`.`field2`',
				['`d1`' => [['joinType' => 'right', 'joinTable' => '`*PREFIX*data2`', 'joinAlias' => '`d2`', 'joinCondition' => '`d1`.`field1` = `d2`.`field2`']]],
				'`*PREFIX*data1` `d1` RIGHT JOIN `*PREFIX*data2` `d2` ON `d1`.`field1` = `d2`.`field2`'
			],
		];
	}

	#[DataProvider(methodName: 'dataRightJoin')]
	public function testRightJoin(string $fromAlias, string $tableName, ?string $tableAlias, ?string $condition, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->from('data1', 'd1');
		$this->queryBuilder->rightJoin(
			$fromAlias,
			$tableName,
			$tableAlias,
			$condition
		);

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('join')
		);

		$this->assertSame(
			'SELECT  FROM ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataSet(): array {
		return [
			['column1', new Literal('value'), null, null, ['`column1` = value'], '`column1` = value'],
			['column1', new Parameter(':param'), null, null, ['`column1` = :param'], '`column1` = :param'],
			['column1', 'column2', null, null, ['`column1` = `column2`'], '`column1` = `column2`'],
			['column1', 'column2', 'column3', new Literal('value'), ['`column1` = `column2`', '`column3` = value'], '`column1` = `column2`, `column3` = value'],
		];
	}

	#[DataProvider(methodName: 'dataSet')]
	public function testSet(string $partOne1, string|ILiteral|IParameter $partOne2, ?string $partTwo1, ?ILiteral $partTwo2, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->update('data');
		$this->queryBuilder->set($partOne1, $partOne2);
		if ($partTwo1 !== null) {
			$this->queryBuilder->set($partTwo1, $partTwo2);
		}

		$this->assertSame(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('set')
		);

		$this->assertSame(
			'UPDATE `*PREFIX*data` SET ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataWhere(): array {
		return [
			[['where1'], new CompositeExpression('AND', ['where1']), 'where1'],
			[['where1', 'where2'], new CompositeExpression('AND', ['where1', 'where2']), '(where1) AND (where2)'],
		];
	}

	#[DataProvider(methodName: 'dataWhere')]
	public function testWhere(array $whereArguments, CompositeExpression $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'where'],
			$whereArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('where')
		);

		$this->assertSame(
			'SELECT `column` WHERE ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	#[DataProvider(methodName: 'dataWhere')]
	public function testAndWhere(array $whereArguments, CompositeExpression $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'andWhere'],
			$whereArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('where')
		);

		$this->assertSame(
			'SELECT `column` WHERE ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataOrWhere(): array {
		return [
			[['where1'], new CompositeExpression('OR', ['where1']), 'where1'],
			[['where1', 'where2'], new CompositeExpression('OR', ['where1', 'where2']), '(where1) OR (where2)'],
		];
	}

	#[DataProvider(methodName: 'dataOrWhere')]
	public function testOrWhere(array $whereArguments, CompositeExpression $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'orWhere'],
			$whereArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('where')
		);

		$this->assertSame(
			'SELECT `column` WHERE ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataGroupBy(): array {
		return [
			[['column1'], ['`column1`'], '`column1`'],
			[['column1', 'column2'], ['`column1`', '`column2`'], '`column1`, `column2`'],
		];
	}

	#[DataProvider(methodName: 'dataGroupBy')]
	public function testGroupBy(array $groupByArguments, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		call_user_func_array(
			[$this->queryBuilder, 'groupBy'],
			$groupByArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('groupBy')
		);

		$this->assertSame(
			'SELECT `column` GROUP BY ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataAddGroupBy(): array {
		return [
			[['column2'], ['`column1`', '`column2`'], '`column1`, `column2`'],
			[['column2', 'column3'], ['`column1`', '`column2`', '`column3`'], '`column1`, `column2`, `column3`'],
		];
	}

	#[DataProvider(methodName: 'dataAddGroupBy')]
	public function testAddGroupBy(array $groupByArguments, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->select('column');
		$this->queryBuilder->groupBy('column1');
		call_user_func_array(
			[$this->queryBuilder, 'addGroupBy'],
			$groupByArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('groupBy')
		);

		$this->assertSame(
			'SELECT `column` GROUP BY ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataSetValue(): array {
		return [
			['column', 'value', ['`column`' => 'value'], '(`column`) VALUES(value)'],
		];
	}

	#[DataProvider(methodName: 'dataSetValue')]
	public function testSetValue(string $column, string $value, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->insert('data');
		$this->queryBuilder->setValue($column, $value);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('values')
		);

		$this->assertSame(
			'INSERT INTO `*PREFIX*data` ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	#[DataProvider(methodName: 'dataSetValue')]
	public function testValues(string $column, string $value, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->insert('data');
		$this->queryBuilder->values([
			$column => $value,
		]);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('values')
		);

		$this->assertSame(
			'INSERT INTO `*PREFIX*data` ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataHaving(): array {
		return [
			[['condition1'], new CompositeExpression('AND', ['condition1']), 'HAVING condition1'],
			[['condition1', 'condition2'], new CompositeExpression('AND', ['condition1', 'condition2']), 'HAVING (condition1) AND (condition2)'],
			[
				[new CompositeExpression('OR', ['condition1', 'condition2'])],
				new CompositeExpression('OR', ['condition1', 'condition2']),
				'HAVING (condition1) OR (condition2)'
			],
			[
				[new CompositeExpression('AND', ['condition1', 'condition2'])],
				new CompositeExpression('AND', ['condition1', 'condition2']),
				'HAVING (condition1) AND (condition2)'
			],
		];
	}

	#[DataProvider(methodName: 'dataHaving')]
	public function testHaving(array $havingArguments, CompositeExpression $expectedQueryPart, string $expectedQuery): void {
		call_user_func_array(
			[$this->queryBuilder, 'having'],
			$havingArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('having')
		);

		$this->assertSame(
			'SELECT  ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataAndHaving(): array {
		return [
			[['condition2'], new CompositeExpression('AND', ['condition1', 'condition2']), 'HAVING (condition1) AND (condition2)'],
			[['condition2', 'condition3'], new CompositeExpression('AND', ['condition1', 'condition2', 'condition3']), 'HAVING (condition1) AND (condition2) AND (condition3)'],
			[
				[new CompositeExpression('OR', ['condition2', 'condition3'])],
				new CompositeExpression('AND', ['condition1', new CompositeExpression('OR', ['condition2', 'condition3'])]),
				'HAVING (condition1) AND ((condition2) OR (condition3))'
			],
			[
				[new CompositeExpression('AND', ['condition2', 'condition3'])],
				new CompositeExpression('AND', ['condition1', new CompositeExpression('AND', ['condition2', 'condition3'])]),
				'HAVING (condition1) AND ((condition2) AND (condition3))'
			],
		];
	}

	#[DataProvider(methodName: 'dataAndHaving')]
	public function testAndHaving(array $havingArguments, CompositeExpression $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->having('condition1');
		call_user_func_array(
			[$this->queryBuilder, 'andHaving'],
			$havingArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('having')
		);

		$this->assertSame(
			'SELECT  ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataOrHaving(): array {
		return [
			[['condition2'], new CompositeExpression('OR', ['condition1', 'condition2']), 'HAVING (condition1) OR (condition2)'],
			[['condition2', 'condition3'], new CompositeExpression('OR', ['condition1', 'condition2', 'condition3']), 'HAVING (condition1) OR (condition2) OR (condition3)'],
			[
				[new CompositeExpression('OR', ['condition2', 'condition3'])],
				new CompositeExpression('OR', ['condition1', new CompositeExpression('OR', ['condition2', 'condition3'])]),
				'HAVING (condition1) OR ((condition2) OR (condition3))'
			],
			[
				[new CompositeExpression('AND', ['condition2', 'condition3'])],
				new CompositeExpression('OR', ['condition1', new CompositeExpression('AND', ['condition2', 'condition3'])]),
				'HAVING (condition1) OR ((condition2) AND (condition3))'
			],
		];
	}

	#[DataProvider(methodName: 'dataOrHaving')]
	public function testOrHaving(array $havingArguments, CompositeExpression $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->having('condition1');
		call_user_func_array(
			[$this->queryBuilder, 'orHaving'],
			$havingArguments
		);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('having')
		);

		$this->assertSame(
			'SELECT  ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataOrderBy(): array {
		return [
			['column', null, ['`column` ASC'], 'ORDER BY `column` ASC'],
			['column', 'ASC', ['`column` ASC'], 'ORDER BY `column` ASC'],
			['column', 'DESC', ['`column` DESC'], 'ORDER BY `column` DESC'],
		];
	}

	/**
	 * @param string|'ASC'|'DESC'|null $order
	 */
	#[DataProvider(methodName: 'dataOrderBy')]
	public function testOrderBy(string $sort, ?string $order, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->orderBy($sort, $order);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('orderBy')
		);

		$this->assertSame(
			'SELECT  ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public static function dataAddOrderBy(): array {
		return [
			['column2', null, null, ['`column1` ASC', '`column2` ASC'], 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', null, 'ASC', ['`column1` ASC', '`column2` ASC'], 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', null, 'DESC', ['`column1` DESC', '`column2` ASC'], 'ORDER BY `column1` DESC, `column2` ASC'],
			['column2', 'ASC', null, ['`column1` ASC', '`column2` ASC'], 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', 'ASC', 'ASC', ['`column1` ASC', '`column2` ASC'], 'ORDER BY `column1` ASC, `column2` ASC'],
			['column2', 'ASC', 'DESC', ['`column1` DESC', '`column2` ASC'], 'ORDER BY `column1` DESC, `column2` ASC'],
			['column2', 'DESC', null, ['`column1` ASC', '`column2` DESC'], 'ORDER BY `column1` ASC, `column2` DESC'],
			['column2', 'DESC', 'ASC', ['`column1` ASC', '`column2` DESC'], 'ORDER BY `column1` ASC, `column2` DESC'],
			['column2', 'DESC', 'DESC', ['`column1` DESC', '`column2` DESC'], 'ORDER BY `column1` DESC, `column2` DESC'],
		];
	}

	/**
	 * @param string|'ASC'|'DESC'|null $order2
	 * @param string|'ASC'|'DESC'|null $order1
	 */
	#[DataProvider(methodName: 'dataAddOrderBy')]
	public function testAddOrderBy(string $sort2, ?string $order2, ?string $order1, array $expectedQueryPart, string $expectedQuery): void {
		$this->queryBuilder->orderBy('column1', $order1);
		$this->queryBuilder->addOrderBy($sort2, $order2);

		$this->assertEquals(
			$expectedQueryPart,
			$this->queryBuilder->getQueryPart('orderBy')
		);

		$this->assertSame(
			'SELECT  ' . $expectedQuery,
			$this->queryBuilder->getSQL()
		);
	}

	public function testGetLastInsertId(): void {
		$qB = $this->connection->getQueryBuilder();

		try {
			$qB->getLastInsertId();
			$this->fail('getLastInsertId() should throw an exception, when being called before insert()');
		} catch (BadMethodCallException) {
			$this->addToAssertionCount(1);
		}

		$qB->insert('properties')
			->values([
				'userid' => $qB->expr()->literal('testFirstResult'),
				'propertypath' => $qB->expr()->literal('testing'),
				'propertyname' => $qB->expr()->literal('testing'),
				'propertyvalue' => $qB->expr()->literal('testing'),
			])
			->executeStatement();

		$actual = $qB->getLastInsertId();

		$this->assertEquals($this->connection->lastInsertId('*PREFIX*properties'), $actual);

		$qB->delete('properties')
			->where($qB->expr()->eq('userid', $qB->expr()->literal('testFirstResult')))
			->executeStatement();

		try {
			$qB->getLastInsertId();
			$this->fail('getLastInsertId() should throw an exception, when being called after delete()');
		} catch (BadMethodCallException) {
			$this->addToAssertionCount(1);
		}
	}

	public static function dataGetTableName(): array {
		return [
			['*PREFIX*table', null, '`*PREFIX*table`'],
			['*PREFIX*table', true, '`*PREFIX*table`'],
			['*PREFIX*table', false, '`*PREFIX*table`'],

			['table', null, '`*PREFIX*table`'],
			['table', true, '`*PREFIX*table`'],
			['table', false, '`table`'],

			['function', null, '(SELECT * FROM `*PREFIX*table`)'],
			['function', true, '(SELECT * FROM `*PREFIX*table`)'],
			['function', false, '(SELECT * FROM `*PREFIX*table`)'],
		];
	}

	#[DataProvider(methodName: 'dataGetTableName')]
	public function testGetTableName(string $tableName, ?bool $automatic, string $expected): void {
		if ($tableName === 'function') {
			$tableName = $this->queryBuilder->createFunction('(' . $this->queryBuilder->select('*')->from('table')->getSQL() . ')');
		}

		if ($automatic !== null) {
			$this->queryBuilder->automaticTablePrefix($automatic);
		}

		$this->assertSame(
			$expected,
			$this->queryBuilder->getTableName($tableName)
		);
	}

	public static function dataGetColumnName(): array {
		return [
			['column', '', '`column`'],
			['column', 'a', '`a`.`column`'],
		];
	}

	#[DataProvider(methodName: 'dataGetColumnName')]
	public function testGetColumnName(string $column, string $prefix, string $expected): void {
		$this->assertSame(
			$expected,
			$this->queryBuilder->getColumnName($column, $prefix)
		);
	}

	private function getConnection(): ConnectionAdapter&MockObject {
		$connection = $this->createMock(ConnectionAdapter::class);
		$connection->method('executeStatement')
			->willReturn(3);
		$connection->method('executeQuery')
			->willReturn($this->createMock(IResult::class));
		return $connection;
	}

	public function testExecuteWithoutLogger(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->method('getType')
			->willReturn(\Doctrine\DBAL\Query\QueryBuilder::INSERT);
		$queryBuilder
			->method('getSQL')
			->willReturn('');
		$queryBuilder
			->method('getParameters')
			->willReturn([]);
		$queryBuilder
			->method('getParameterTypes')
			->willReturn([]);
		$this->logger
			->expects($this->never())
			->method('debug');
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(false);

		$this->invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->invokePrivate($this->queryBuilder, 'connection', [$this->getConnection()]);
		$this->assertEquals(3, $this->queryBuilder->executeStatement());
	}

	public function testExecuteWithLoggerAndNamedArray(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->method('getType')
			->willReturn(\Doctrine\DBAL\Query\QueryBuilder::INSERT);
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn([
				'foo' => 'bar',
				'key' => 'value',
			]);
		$queryBuilder
			->method('getParameterTypes')
			->willReturn([
				'foo' => IQueryBuilder::PARAM_STR,
				'key' => IQueryBuilder::PARAM_STR,
			]);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('UPDATE FOO SET bar = 1 WHERE BAR = ?');
		$this->logger
			->expects($this->once())
			->method('debug')
			->with(
				'DB QueryBuilder: \'{query}\' with parameters: {params}',
				[
					'query' => 'UPDATE FOO SET bar = 1 WHERE BAR = ?',
					'params' => 'foo => \'bar\', key => \'value\'',
					'app' => 'core',
				]
			);
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(true);

		$this->invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->invokePrivate($this->queryBuilder, 'connection', [$this->getConnection()]);
		$this->assertEquals(3, $this->queryBuilder->executeStatement());
	}

	public function testExecuteWithLoggerAndUnnamedArray(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->method('getType')
			->willReturn(\Doctrine\DBAL\Query\QueryBuilder::INSERT);
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn(['Bar']);
		$queryBuilder
			->method('getParameterTypes')
			->willReturn([IQueryBuilder::PARAM_STR]);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('UPDATE FOO SET bar = false WHERE BAR = ?');
		$this->logger
			->expects($this->once())
			->method('debug')
			->with(
				'DB QueryBuilder: \'{query}\' with parameters: {params}',
				[
					'query' => 'UPDATE FOO SET bar = false WHERE BAR = ?',
					'params' => '0 => \'Bar\'',
					'app' => 'core',
				]
			);
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(true);

		$this->invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->invokePrivate($this->queryBuilder, 'connection', [$this->getConnection()]);
		$this->assertEquals(3, $this->queryBuilder->executeStatement());
	}

	public function testExecuteWithLoggerAndNoParams(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$queryBuilder
			->method('getType')
			->willReturn(\Doctrine\DBAL\Query\QueryBuilder::INSERT);
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn([]);
		$queryBuilder
			->method('getParameterTypes')
			->willReturn([]);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('UPDATE FOO SET bar = false WHERE BAR = ?');
		$this->logger
			->expects($this->once())
			->method('debug')
			->with(
				'DB QueryBuilder: \'{query}\'',
				[
					'query' => 'UPDATE FOO SET bar = false WHERE BAR = ?',
					'app' => 'core',
				]
			);
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('log_query', false)
			->willReturn(true);

		$this->invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->invokePrivate($this->queryBuilder, 'connection', [$this->getConnection()]);
		$this->assertEquals(3, $this->queryBuilder->executeStatement());
	}

	public function testExecuteWithParameterTooLarge(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$p = array_fill(0, 1001, 'foo');
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn([$p]);
		$queryBuilder
			->method('getParameterTypes')
			->willReturn([IQueryBuilder::PARAM_STR_ARRAY]);
		$queryBuilder
			->expects($this->any())
			->method('getType')
			->willReturn(\Doctrine\DBAL\Query\QueryBuilder::SELECT);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('SELECT * FROM FOO WHERE BAR IN (?)');
		$this->logger
			->expects($this->once())
			->method('error')
			->willReturnCallback(function ($message, $parameters): void {
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

		$this->invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->invokePrivate($this->queryBuilder, 'connection', [$this->getConnection()]);
		$this->queryBuilder->executeQuery();
	}

	public function testExecuteWithParametersTooMany(): void {
		$queryBuilder = $this->createMock(\Doctrine\DBAL\Query\QueryBuilder::class);
		$p = array_fill(0, 999, 'foo');
		$queryBuilder
			->expects($this->any())
			->method('getParameters')
			->willReturn(array_fill(0, 66, $p));
		$queryBuilder
			->method('getParameterTypes')
			->willReturn([IQueryBuilder::PARAM_STR_ARRAY]);
		$queryBuilder
			->expects($this->any())
			->method('getType')
			->willReturn(\Doctrine\DBAL\Query\QueryBuilder::SELECT);
		$queryBuilder
			->expects($this->any())
			->method('getSQL')
			->willReturn('SELECT * FROM FOO WHERE BAR IN (?) OR BAR IN (?)');
		$this->logger
			->expects($this->once())
			->method('error')
			->willReturnCallback(function ($message, $parameters): void {
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

		$this->invokePrivate($this->queryBuilder, 'queryBuilder', [$queryBuilder]);
		$this->invokePrivate($this->queryBuilder, 'connection', [$this->getConnection()]);
		$this->queryBuilder->executeQuery();
	}
}
