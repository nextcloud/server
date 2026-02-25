<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\DB\QueryBuilder;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder as DoctrineExpressionBuilder;
use OC\DB\Connection;
use OC\DB\QueryBuilder\ExpressionBuilder\ExpressionBuilder;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[Group(name: 'DB')]
class ExpressionBuilderTest extends TestCase {
	protected ExpressionBuilder $expressionBuilder;
	protected DoctrineExpressionBuilder $doctrineExpressionBuilder;
	protected IDBConnection $connection;
	protected \Doctrine\DBAL\Connection $internalConnection;
	protected LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->internalConnection = Server::get(Connection::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$queryBuilder->method('func')
			->willReturn($this->createMock(IFunctionBuilder::class));

		$this->expressionBuilder = new ExpressionBuilder($this->connection, $queryBuilder, $this->logger);

		$this->doctrineExpressionBuilder = new DoctrineExpressionBuilder($this->internalConnection);
	}

	public static function dataComparison(): array {
		$valueSets = self::dataComparisons();
		$comparisonOperators = ['=', '<>', '<', '>', '<=', '>='];

		$testSets = [];
		foreach ($comparisonOperators as $operator) {
			foreach ($valueSets as $values) {
				$testSets[] = array_merge([$operator], $values);
			}
		}
		return $testSets;
	}

	#[DataProvider(methodName: 'dataComparison')]
	public function testComparison(string $comparison, mixed $input1, bool $isInput1Literal, mixed $input2, bool $isInput2Literal): void {
		[$doctrineInput1, $ocInput1] = $this->helpWithLiteral($input1, $isInput1Literal);
		[$doctrineInput2, $ocInput2] = $this->helpWithLiteral($input2, $isInput2Literal);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->comparison($doctrineInput1, $comparison, $doctrineInput2),
			$this->expressionBuilder->comparison($ocInput1, $comparison, $ocInput2)
		);
	}

	public static function dataComparisons(): array {
		return [
			['value', false, 'value', false],
			['value', false, 'value', true],
			['value', true, 'value', false],
			['value', true, 'value', true],
		];
	}

	#[DataProvider(methodName: 'dataComparisons')]
	public function testEquals(string $input1, bool $isInput1Literal, string $input2, bool $isInput2Literal): void {
		[$doctrineInput1, $ocInput1] = $this->helpWithLiteral($input1, $isInput1Literal);
		[$doctrineInput2, $ocInput2] = $this->helpWithLiteral($input2, $isInput2Literal);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->eq($doctrineInput1, $doctrineInput2),
			$this->expressionBuilder->eq($ocInput1, $ocInput2)
		);
	}

	#[DataProvider(methodName: 'dataComparisons')]
	public function testNotEquals(string $input1, bool $isInput1Literal, string $input2, bool $isInput2Literal): void {
		[$doctrineInput1, $ocInput1] = $this->helpWithLiteral($input1, $isInput1Literal);
		[$doctrineInput2, $ocInput2] = $this->helpWithLiteral($input2, $isInput2Literal);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->neq($doctrineInput1, $doctrineInput2),
			$this->expressionBuilder->neq($ocInput1, $ocInput2)
		);
	}

	#[DataProvider(methodName: 'dataComparisons')]
	public function testLowerThan(string $input1, bool $isInput1Literal, string $input2, bool $isInput2Literal): void {
		[$doctrineInput1, $ocInput1] = $this->helpWithLiteral($input1, $isInput1Literal);
		[$doctrineInput2, $ocInput2] = $this->helpWithLiteral($input2, $isInput2Literal);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->lt($doctrineInput1, $doctrineInput2),
			$this->expressionBuilder->lt($ocInput1, $ocInput2)
		);
	}

	#[DataProvider(methodName: 'dataComparisons')]
	public function testLowerThanEquals(string $input1, bool $isInput1Literal, string $input2, bool $isInput2Literal): void {
		[$doctrineInput1, $ocInput1] = $this->helpWithLiteral($input1, $isInput1Literal);
		[$doctrineInput2, $ocInput2] = $this->helpWithLiteral($input2, $isInput2Literal);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->lte($doctrineInput1, $doctrineInput2),
			$this->expressionBuilder->lte($ocInput1, $ocInput2)
		);
	}

	#[DataProvider(methodName: 'dataComparisons')]
	public function testGreaterThan(string $input1, bool $isInput1Literal, string $input2, bool $isInput2Literal): void {
		[$doctrineInput1, $ocInput1] = $this->helpWithLiteral($input1, $isInput1Literal);
		[$doctrineInput2, $ocInput2] = $this->helpWithLiteral($input2, $isInput2Literal);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->gt($doctrineInput1, $doctrineInput2),
			$this->expressionBuilder->gt($ocInput1, $ocInput2)
		);
	}

	#[DataProvider(methodName: 'dataComparisons')]
	public function testGreaterThanEquals(string $input1, bool $isInput1Literal, string $input2, bool $isInput2Literal): void {
		[$doctrineInput1, $ocInput1] = $this->helpWithLiteral($input1, $isInput1Literal);
		[$doctrineInput2, $ocInput2] = $this->helpWithLiteral($input2, $isInput2Literal);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->gte($doctrineInput1, $doctrineInput2),
			$this->expressionBuilder->gte($ocInput1, $ocInput2)
		);
	}

	public function testIsNull(): void {
		$this->assertEquals(
			$this->doctrineExpressionBuilder->isNull('`test`'),
			$this->expressionBuilder->isNull('test')
		);
	}

	public function testIsNotNull(): void {
		$this->assertEquals(
			$this->doctrineExpressionBuilder->isNotNull('`test`'),
			$this->expressionBuilder->isNotNull('test')
		);
	}

	public static function dataLike(): array {
		return [
			['value', false],
			['value', true],
		];
	}

	#[DataProvider(methodName: 'dataLike')]
	public function testLike(string $input, bool $isLiteral): void {
		[$doctrineInput, $ocInput] = $this->helpWithLiteral($input, $isLiteral);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->like('`test`', $doctrineInput),
			$this->expressionBuilder->like('test', $ocInput)
		);
	}

	#[DataProvider(methodName: 'dataLike')]
	public function testNotLike(string $input, bool $isLiteral): void {
		[$doctrineInput, $ocInput] = $this->helpWithLiteral($input, $isLiteral);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->notLike('`test`', $doctrineInput),
			$this->expressionBuilder->notLike('test', $ocInput)
		);
	}

	public static function dataIn(): array {
		return [
			['value', false],
			['value', true],
			[['value'], false],
			[['value'], true],
		];
	}

	#[DataProvider(methodName: 'dataIn')]
	public function testIn(string|array $input, bool $isLiteral): void {
		[$doctrineInput, $ocInput] = $this->helpWithLiteral($input, $isLiteral);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->in('`test`', $doctrineInput),
			$this->expressionBuilder->in('test', $ocInput)
		);
	}

	#[DataProvider(methodName: 'dataIn')]
	public function testNotIn(string|array $input, bool $isLiteral): void {
		[$doctrineInput, $ocInput] = $this->helpWithLiteral($input, $isLiteral);

		$this->assertEquals(
			$this->doctrineExpressionBuilder->notIn('`test`', $doctrineInput),
			$this->expressionBuilder->notIn('test', $ocInput)
		);
	}

	protected function helpWithLiteral(string|array $input, bool $isLiteral): array {
		if ($isLiteral) {
			if (is_array($input)) {
				$doctrineInput = array_map(function ($ident) {
					return $this->doctrineExpressionBuilder->literal($ident);
				}, $input);
				$ocInput = array_map(function ($ident) {
					return $this->expressionBuilder->literal($ident);
				}, $input);
			} else {
				$doctrineInput = $this->doctrineExpressionBuilder->literal($input);
				$ocInput = $this->expressionBuilder->literal($input);
			}
		} else {
			if (is_array($input)) {
				$doctrineInput = array_map(function ($input) {
					return '`' . $input . '`';
				}, $input);
				$ocInput = $input;
			} else {
				$doctrineInput = '`' . $input . '`';
				$ocInput = $input;
			}
		}

		return [$doctrineInput, $ocInput];
	}

	public static function dataLiteral(): array {
		return [
			['value', null],
			['1', null],
			[1, null],
			[1, 'string'],
			[1, 'integer'],
			[1, IQueryBuilder::PARAM_INT],
		];
	}

	#[DataProvider(methodName: 'dataLiteral')]
	public function testLiteral(string|int $input, string|int|null $type): void {
		if ($type === null) {
			$actual = $this->expressionBuilder->literal($input);
		} else {
			$actual = $this->expressionBuilder->literal($input, $type);
		}

		$this->assertEquals(
			$this->doctrineExpressionBuilder->literal($input, $type),
			$actual->__toString()
		);
	}

	public static function dataClobComparisons(): array {
		return [
			['eq', '5', IQueryBuilder::PARAM_STR, false, 3],
			['eq', '5', IQueryBuilder::PARAM_STR, true, 1],
			['neq', '5', IQueryBuilder::PARAM_STR, false, 8],
			['neq', '5', IQueryBuilder::PARAM_STR, true, 6],
			['lt', '5', IQueryBuilder::PARAM_STR, false, 3],
			['lt', '5', IQueryBuilder::PARAM_STR, true, 1],
			['lte', '5', IQueryBuilder::PARAM_STR, false, 6],
			['lte', '5', IQueryBuilder::PARAM_STR, true, 4],
			['gt', '5', IQueryBuilder::PARAM_STR, false, 5],
			['gt', '5', IQueryBuilder::PARAM_STR, true, 1],
			['gte', '5', IQueryBuilder::PARAM_STR, false, 8],
			['gte', '5', IQueryBuilder::PARAM_STR, true, 4],
			['like', '%5%', IQueryBuilder::PARAM_STR, false, 3],
			['like', '%5%', IQueryBuilder::PARAM_STR, true, 1],
			['like', 'under_%', IQueryBuilder::PARAM_STR, false, 2],
			['like', 'under\_%', IQueryBuilder::PARAM_STR, false, 1],
			['notLike', '%5%', IQueryBuilder::PARAM_STR, false, 8],
			['notLike', '%5%', IQueryBuilder::PARAM_STR, true, 6],
			['in', ['5'], IQueryBuilder::PARAM_STR_ARRAY, false, 3],
			['in', ['5'], IQueryBuilder::PARAM_STR_ARRAY, true, 1],
			['notIn', ['5'], IQueryBuilder::PARAM_STR_ARRAY, false, 8],
			['notIn', ['5'], IQueryBuilder::PARAM_STR_ARRAY, true, 6],
		];
	}

	/**
	 * @param IQueryBuilder::PARAM_* $type
	 */
	#[DataProvider(methodName: 'dataClobComparisons')]
	public function testClobComparisons(string $function, string|array $value, mixed $type, bool $compareKeyToValue, int $expected): void {
		$appId = $this->getUniqueID('testing');
		$this->createConfig($appId, 1, 4);
		$this->createConfig($appId, 2, 5);
		$this->createConfig($appId, 3, 6);
		$this->createConfig($appId, 4, 4);
		$this->createConfig($appId, 5, 5);
		$this->createConfig($appId, 6, 6);
		$this->createConfig($appId, 7, 4);
		$this->createConfig($appId, 8, 5);
		$this->createConfig($appId, 9, 6);
		$this->createConfig($appId, 10, 'under_score');
		$this->createConfig($appId, 11, 'underscore');

		$query = $this->connection->getQueryBuilder();
		$query->select($query->func()->count('*', 'count'))
			->from('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter($appId)))
			->andWhere(call_user_func([$query->expr(), $function], 'configvalue', $query->createNamedParameter($value, $type), IQueryBuilder::PARAM_STR));

		if ($compareKeyToValue) {
			$query->andWhere(call_user_func([$query->expr(), $function], 'configkey', 'configvalue', IQueryBuilder::PARAM_STR));
		}

		$result = $query->executeQuery();

		$this->assertEquals(['count' => $expected], $result->fetchAssociative());
		$result->closeCursor();

		$query = $this->connection->getQueryBuilder();
		$query->delete('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter($appId)))
			->executeStatement();
	}
	protected function createConfig(string $appId, int $key, string|int $value): void {
		$query = $this->connection->getQueryBuilder();
		$query->insert('appconfig')
			->values([
				'appid' => $query->createNamedParameter($appId),
				'configkey' => $query->createNamedParameter((string)$key),
				'configvalue' => $query->createNamedParameter((string)$value),
			])
			->executeStatement();
	}
}
