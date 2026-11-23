<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Command\Object;

use OCA\Files\Command\Object\ObjectUtil;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\IResult;
use OCP\IConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ObjectUtilTest extends TestCase {
	private IConfig&MockObject $config;
	private IDBConnection&MockObject $connection;
	private IQueryBuilder&MockObject $query;
	private IExpressionBuilder&MockObject $expr;
	private ObjectUtil $util;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->query = $this->createMock(IQueryBuilder::class);
		$this->expr = $this->createMock(IExpressionBuilder::class);

		$this->connection->method('getQueryBuilder')->willReturn($this->query);
		$this->query->method('expr')->willReturn($this->expr);
		$this->query->method('select')->willReturnSelf();
		$this->query->method('from')->willReturnSelf();
		$this->query->method('where')->willReturnSelf();
		$this->query->method('andWhere')->willReturnSelf();
		$this->query->method('createNamedParameter')->willReturnCallback(
			fn (mixed $value, int $type = IQueryBuilder::PARAM_STR) => 'param_' . $value,
		);
		$result = $this->createMock(IResult::class);
		$result->method('fetchOne')->willReturn(123);
		$this->query->method('executeQuery')->willReturn($result);

		$this->util = new ObjectUtil($this->config, $this->connection);
	}

	public function testObjectExistsInDbChecksStorageWhenProvided(): void {
		$conditions = [];
		$this->expr->expects($this->exactly(2))
			->method('eq')
			->willReturnCallback(function (string $column, string $parameter) use (&$conditions): string {
				$conditions[] = [$column, $parameter];
				return $column . '_match';
			});
		$this->query->expects($this->once())
			->method('andWhere')
			->with('storage_match')
			->willReturnSelf();

		$result = $this->util->objectExistsInDb('urn:oid:123', 7);

		$this->assertSame(123, $result);
		$this->assertSame([
			['fileid', 'param_123'],
			['storage', 'param_7'],
		], $conditions);
	}
}
