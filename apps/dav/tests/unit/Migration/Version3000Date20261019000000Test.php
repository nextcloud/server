<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Migration;

use OCA\DAV\Migration\Version3000Date20261019000000;
use OCP\DB\IResult;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Schema\ITable;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Security\ICrypto;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class Version3000Date20261019000000Test extends TestCase {
	private IDBConnection&MockObject $connection;
	private IOutput&MockObject $output;
	private Version3000Date20261019000000 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock(IDBConnection::class);
		$this->output = $this->createMock(IOutput::class);

		$this->migration = new Version3000Date20261019000000($this->connection);
	}

	public function testChangeSchemaAddsColumn(): void {
		$table = $this->createMock(ITable::class);
		$table->method('hasColumn')->with('credentials')->willReturn(false);
		$table->expects($this->once())
			->method('addColumn')
			->with('credentials', Types::TEXT, ['notnull' => false]);
		$schema = $this->createMock(ISchemaWrapper::class);
		$schema->method('getTable')->with('calendarsubscriptions')->willReturn($table);

		$this->assertSame($schema, $this->migration->changeSchema($this->output, fn () => $schema, []));
	}

	public function testChangeSchemaColumnExists(): void {
		$table = $this->createMock(ITable::class);
		$table->method('hasColumn')->with('credentials')->willReturn(true);
		$table->expects($this->never())->method('addColumn');
		$schema = $this->createMock(ISchemaWrapper::class);
		$schema->method('getTable')->with('calendarsubscriptions')->willReturn($table);

		$this->assertNull($this->migration->changeSchema($this->output, fn () => $schema, []));
	}

	public function testPostSchemaChangeMovesCredentials(): void {
		$result = $this->createMock(IResult::class);
		$result->method('fetchAssociative')->willReturnOnConsecutiveCalls(
			['id' => '1', 'source' => 'https://user:pass@example.com/cal.ics'],
			['id' => '2', 'source' => 'https://example.com/user@domain/cal.ics'],
			false,
		);

		$select = $this->createMock(IQueryBuilder::class);
		$select->method('expr')->willReturn($this->createMock(IExpressionBuilder::class));
		$select->method('select')->willReturnSelf();
		$select->method('from')->willReturnSelf();
		$select->method('where')->willReturnSelf();
		$select->method('andWhere')->willReturnSelf();
		$select->method('executeQuery')->willReturn($result);

		$parameters = [];
		$update = $this->createMock(IQueryBuilder::class);
		$update->method('expr')->willReturn($this->createMock(IExpressionBuilder::class));
		$update->method('update')->willReturnSelf();
		$update->method('set')->willReturnSelf();
		$update->method('where')->willReturnSelf();
		$update->method('setParameter')->willReturnCallback(function (string $key, mixed $value) use (&$parameters, $update) {
			$parameters[$key] = $value;
			return $update;
		});
		$update->expects($this->once())
			->method('executeStatement')
			->willReturnCallback(function () use (&$parameters) {
				$this->assertSame('https://example.com/cal.ics', $parameters['source']);
				$this->assertSame(1, $parameters['id']);
				$this->assertSame(
					['username' => 'user', 'password' => 'pass'],
					json_decode(Server::get(ICrypto::class)->decrypt($parameters['credentials']), true),
				);
				return 1;
			});

		$this->connection->method('getQueryBuilder')->willReturnOnConsecutiveCalls($select, $update);

		$this->migration->postSchemaChange($this->output, fn () => null, []);
	}
}
