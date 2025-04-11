<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\DB\QueryBuilder\Partitioned;

use OC\DB\ConnectionAdapter;
use OC\DB\QueryBuilder\Partitioned\JoinCondition;
use OC\DB\QueryBuilder\QueryBuilder;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class JoinConditionTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
	}

	public function platformProvider(): array {
		return [
			[IDBConnection::PLATFORM_SQLITE],
			[IDBConnection::PLATFORM_POSTGRES],
			[IDBConnection::PLATFORM_MYSQL],
			[IDBConnection::PLATFORM_ORACLE],
		];
	}

	private function getBuilder(string $platform): IQueryBuilder {
		$connection = $this->createMock(ConnectionAdapter::class);
		$connection->method('getDatabaseProvider')->willReturn($platform);
		return new QueryBuilder(
			$connection,
			$this->createMock(SystemConfig::class),
			$this->createMock(LoggerInterface::class)
		);
	}

	/**
	 * @dataProvider platformProvider
	 */
	public function testParseCondition(string $platform): void {
		$query = $this->getBuilder($platform);
		$param1 = $query->createNamedParameter('files');
		$param2 = $query->createNamedParameter('test');
		$condition = $query->expr()->andX(
			$query->expr()->eq('tagmap.categoryid', 'tag.id'),
			$query->expr()->eq('tag.type', $param1),
			$query->expr()->eq('tag.uid', $param2)
		);
		$parsed = JoinCondition::parse($condition, 'vcategory', 'tag', 'tagmap');
		$this->assertEquals('tagmap.categoryid', $parsed->fromColumn);
		$this->assertEquals('tag.id', $parsed->toColumn);
		$this->assertEquals([], $parsed->fromConditions);
		$this->assertEquals([
			$query->expr()->eq('tag.type', $param1),
			$query->expr()->eq('tag.uid', $param2),
		], $parsed->toConditions);
	}

	/**
	 * @dataProvider platformProvider
	 */
	public function testParseCastCondition(string $platform): void {
		$query = $this->getBuilder($platform);

		$condition = $query->expr()->eq($query->expr()->castColumn('m.objectid', IQueryBuilder::PARAM_INT), 'f.fileid');
		$parsed = JoinCondition::parse($condition, 'filecache', 'f', 'm');
		$this->assertEquals('m.objectid', $parsed->fromColumn);
		$this->assertEquals('f.fileid', $parsed->toColumn);
		$this->assertEquals([], $parsed->fromConditions);
	}
}
