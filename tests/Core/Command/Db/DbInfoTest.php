<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Command\Db;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Result;
use OC\Core\Command\Db\DbInfo;
use OC\DB\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Test\TestCase;

class DbInfoTest extends TestCase {

	private Connection&MockObject $connection;
	private InputInterface&MockObject $input;
	private DbInfo $command;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = $this->createMock(Connection::class);
		$this->input      = $this->createMock(InputInterface::class);
		$this->command    = new DbInfo($this->connection);
	}

	private function mockMySQLResult(array $overrides = []): Result&MockObject {
		$result = $this->createMock(Result::class);
		$result->method('fetchAssociative')->willReturn(array_merge([
			'version'      => '8.0.30',
			'buffer_pool'  => 1073741824,  // 1 GB
			'max_conn'     => '200',
			'charset'      => 'utf8mb4',
			'tx_isolation' => 'READ-COMMITTED',
		], $overrides));
		return $result;
	}

	private function mockPostgreSQLResult(): Result&MockObject {
		$result = $this->createMock(Result::class);
		$result->method('fetchAssociative')->willReturn([
			'version'        => 'PostgreSQL 15.2 on x86_64',
			'max_conn'       => '100',
			'shared_buffers' => '128MB',
			'work_mem'       => '4MB',
		]);
		return $result;
	}

	public function testMySQLTableOutput(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(MySQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockMySQLResult());
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$content = $output->fetch();
		$this->assertStringContainsString('Setting', $content);
		$this->assertStringContainsString('MySQL/MariaDB', $content);
	}

	public function testPostgreSQLTableOutput(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(PostgreSQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockPostgreSQLResult());
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$this->assertStringContainsString('PostgreSQL', $output->fetch());
	}

	public function testSQLiteTableOutput(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(SqlitePlatform::class));
		$result = $this->createMock(Result::class);
		$result->method('fetchAssociative')->willReturn(['version' => '3.43.0']);
		$this->connection->method('executeQuery')->willReturn($result);
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$this->assertStringContainsString('SQLite', $output->fetch());
	}

	public function testUnsupportedPlatformReturnsFailure(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(AbstractPlatform::class));
		$this->input->method('getOption')->willReturnMap([['json', false]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(1, $exit);
		$this->assertStringContainsString('Unsupported', $output->fetch());
	}

	public function testJsonOutputContainsSettingKeys(): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(MySQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockMySQLResult());
		$this->input->method('getOption')->willReturnMap([['json', true]]);

		$output = new BufferedOutput();
		$exit = self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$this->assertSame(0, $exit);
		$data = json_decode($output->fetch(), true);
		$this->assertIsArray($data);
		$this->assertArrayHasKey('setting', $data[0]);
		$this->assertArrayHasKey('value',   $data[0]);
	}

	public static function dataMySQLHealthChecks(): array {
		return [
			'charset utf8mb4 → OK'                 => ['charset',      'utf8mb4',         true,  'Character Set'],
			'charset latin1 → CHECK'                => ['charset',      'latin1',          false, 'Character Set'],
			'max_conn 200 → OK'                     => ['max_conn',     '200',             true,  'Max Connections'],
			'max_conn 50 → CHECK'                   => ['max_conn',     '50',              false, 'Max Connections'],
			'tx_isolation READ-COMMITTED → OK'      => ['tx_isolation', 'READ-COMMITTED',  true,  'Transaction Isolation'],
			'tx_isolation REPEATABLE-READ → CHECK'  => ['tx_isolation', 'REPEATABLE-READ', false, 'Transaction Isolation'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataMySQLHealthChecks')]
	public function testMySQLHealthCheckStatus(
		string $field,
		string $value,
		bool $expectedOk,
		string $settingLabel,
	): void {
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(MySQLPlatform::class));
		$this->connection->method('executeQuery')
			->willReturn($this->mockMySQLResult([$field => $value]));
		$this->input->method('getOption')->willReturnMap([['json', true]]);

		$output = new BufferedOutput();
		self::invokePrivate($this->command, 'execute', [$this->input, $output]);

		$data = json_decode($output->fetch(), true);
		$rows = array_values(array_filter($data, fn($r) => $r['setting'] === $settingLabel));
		$this->assertNotEmpty($rows, "Setting '{$settingLabel}' not found in JSON output");
		$this->assertSame($expectedOk, $rows[0]['ok']);
	}
}
