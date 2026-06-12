<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Db;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use OC\Core\Command\Db\DbLocks;
use OC\DB\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Test\TestCase;

class DbLocksTest extends TestCase {

	private Connection&MockObject $connection;
	private InputInterface&MockObject $input;
	private DbLocks $command;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = $this->createMock(Connection::class);
		$this->input = $this->createMock(InputInterface::class);
		$this->command = new DbLocks($this->connection);
	}

	private function mockMySQLLocks(): array {
		return [[
			'waiting_trx_id' => '12345',
			'waiting_thread' => '42',
			'waiting_query' => 'UPDATE oc_filecache SET path_hash = ?',
			'blocking_trx_id' => '12344',
			'blocking_thread' => '41',
			'blocking_query' => null,  // NULL — deve ser renderizado como '—'
		]];
	}

	private function mockPostgreSQLLocks(): array {
		return [[
			'blocked_pid' => 1234,
			'blocked_user' => 'nextcloud',
			'blocking_pid' => 1233,
			'blocking_user' => 'nextcloud',
			'blocked_query' => 'SELECT * FROM oc_filecache WHERE parent = ?',
			'blocked_duration' => '00:00:05.123456',
		]];
	}

	private function mockResult(array $rows): Result&MockObject {
		$result = $this->createMock(Result::class);
		$result->method('fetchAllAssociative')->willReturn($rows);
		return $result;
	}

	public function testMySQLNoLocksShowsInfoMessage(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(MySQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockResult([]));
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$this->assertStringContainsString('No active locks', $output->fetch());
	}

	public function testPostgreSQLNoLocksShowsInfoMessage(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(PostgreSQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockResult([]));
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$this->assertStringContainsString('No active locks', $output->fetch());
	}

	public function testMySQLLocksFoundShowsErrorMessage(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(MySQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockResult($this->mockMySQLLocks()));
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$this->assertStringContainsString('Found 1 blocking transaction(s)', $output->fetch());
	}

	public function testPostgreSQLLocksFoundShowsErrorMessage(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(PostgreSQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockResult($this->mockPostgreSQLLocks()));
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$this->assertStringContainsString('Found 1 blocking transaction(s)', $output->fetch());
	}

	public function testJsonOutputWhenLocksExist(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(MySQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockResult($this->mockMySQLLocks()));
		$this->input->method('getOption')->willReturnMap([['json', true]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$data = json_decode($output->fetch(), true);
		$this->assertIsArray($data);
		$this->assertCount(1, $data);
		$this->assertArrayHasKey('waiting_trx_id', $data[0]);
	}

	public function testSQLiteReturnsSuccessWithMessage(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(SqlitePlatform::class));
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$this->assertStringContainsString('file-level locking', $output->fetch());
	}

	public function testNullColumnRenderedAsDash(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(MySQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockResult($this->mockMySQLLocks()));  // blocking_query = null
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertStringContainsString('—', $output->fetch());
	}
}
