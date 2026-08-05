<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Setup;

use OC\DB\Connection;
use OC\DB\ConnectionFactory;
use OC\SystemConfig;
use OCP\IL10N;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AbstractDatabaseTest extends TestCase {
	private SystemConfig&MockObject $config;
	private ConnectionFactory&MockObject $connectionFactory;
	private Connection&MockObject $connection;
	private TestDatabase $database;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(SystemConfig::class);
		$this->connectionFactory = $this->createMock(ConnectionFactory::class);
		$this->connection = $this->createMock(Connection::class);

		$this->database = new TestDatabase(
			$this->createMock(IL10N::class),
			$this->config,
			$this->createMock(LoggerInterface::class),
			$this->createMock(ISecureRandom::class),
		);
		$this->database->connectionFactory = $this->connectionFactory;
	}

	public function testInitializeWritesConnectionConfig(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org:5432',
				'dbtableprefix' => 'nc_',
			]);

		$this->database->initialize([
			'dbuser' => 'admin',
			'dbpass' => 'admin-password',
			'dbname' => 'nextcloud',
			'dbhost' => 'db.example.org:5432',
			'dbtableprefix' => 'nc_',
		]);
	}

	public function testInitializeFallsBackToLocalhost(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'localhost',
				'dbtableprefix' => 'oc_',
			]);

		$this->database->initialize([
			'dbuser' => 'admin',
			'dbpass' => 'admin-password',
			'dbname' => 'nextcloud',
			'dbhost' => '',
		]);
	}

	/**
	 * Host, database name and table prefix must not be passed as additional parameters:
	 * they are resolved from the system config by the connection factory, so that setup
	 * connects exactly like the installed instance will.
	 */
	public function testConnectOnlyPassesCredentials(): void {
		$this->expectConnection('mysql', [
			'user' => 'admin',
			'password' => 'admin-password',
		]);

		$this->database->initialize($this->options());

		$this->assertSame($this->connection, $this->database->connectForTest());
	}

	public function testConnectPassesPort(): void {
		$this->expectConnection('pgsql', [
			'user' => 'admin',
			'password' => 'admin-password',
			'port' => 5432,
		]);

		$this->database->initialize($this->options(['dbport' => '5432']));
		$this->database->connectForTest();
	}

	public function testConnectPassesSocket(): void {
		$this->expectConnection('mysql', [
			'user' => 'admin',
			'password' => 'admin-password',
			'unix_socket' => '/var/run/mysqld/mysqld.sock',
		]);

		$this->database->initialize($this->options(['dbport' => '/var/run/mysqld/mysqld.sock']));
		$this->database->connectForTest();
	}

	public function testConnectAppliesConfigOverwrite(): void {
		$this->expectConnection('pgsql', [
			'user' => 'admin',
			'password' => 'admin-password',
			'dbname' => 'postgres',
		]);

		$this->database->initialize($this->options());
		$this->database->connectForTest(['dbname' => 'postgres']);
	}

	private function options(array $additional = []): array {
		return array_merge([
			'dbuser' => 'admin',
			'dbpass' => 'admin-password',
			'dbname' => 'nextcloud',
			'dbhost' => 'db.example.org',
		], $additional);
	}

	private function expectConnection(string $dbType, array $expectedParams): void {
		$this->config->method('getValue')
			->willReturnCallback(fn ($key, $default = '') => $key === 'dbtype' ? $dbType : $default);

		$this->connectionFactory->expects($this->once())
			->method('getConnection')
			->with($dbType, $expectedParams)
			->willReturn($this->connection);

		$this->connection->expects($this->once())
			->method('ensureConnectedToPrimary');
	}
}
