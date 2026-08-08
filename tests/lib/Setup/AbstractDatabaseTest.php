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
	/**
	 * Numeric literal instead of PDO::MYSQL_ATTR_SSL_CA: the constant is deprecated
	 * since PHP 8.5 and only defined when the MySQL driver is available.
	 */
	private const MYSQL_ATTR_SSL_CA = 1008;

	private SystemConfig&MockObject $config;
	private ConnectionFactory&MockObject $connectionFactory;
	private Connection&MockObject $connection;
	private LoggerInterface&MockObject $logger;
	private TestDatabase $database;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(SystemConfig::class);
		$this->connectionFactory = $this->createMock(ConnectionFactory::class);
		$this->connection = $this->createMock(Connection::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')
			->willReturnCallback(fn (string $text, array $parameters = []) => vsprintf($text, $parameters));

		$this->database = new TestDatabase(
			$l10n,
			$this->config,
			$this->logger,
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
	 * The connection encryption options are only read from the system config, so they have
	 * to be persisted by initialize() - before any connection is opened by setupDatabase().
	 */
	public function testInitializePersistsDriverOptions(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
				'dbdriveroptions' => [self::MYSQL_ATTR_SSL_CA => '/ca.pem'],
			]);

		$this->database->initialize($this->options([
			'dbdriveroptions' => [self::MYSQL_ATTR_SSL_CA => '/ca.pem'],
		]));
	}

	/**
	 * Only the options of the database being set up may be persisted, every database
	 * configures an encrypted connection differently.
	 */
	public function testInitializeSkipsOptionsOfOtherDatabases(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
			]);

		$this->database->initialize($this->options([
			'pgsql_ssl' => ['mode' => 'verify-full'],
		]));
	}

	public static function emptyEncryptionOptions(): array {
		return [
			'not provided' => [[]],
			'empty array' => [['dbdriveroptions' => []]],
			'null' => [['dbdriveroptions' => null]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('emptyEncryptionOptions')]
	public function testInitializeSkipsEmptyEncryptionOptions(array $additional): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
			]);

		$this->database->initialize($this->options($additional));
	}

	/**
	 * A malformed option must never be persisted, as that would end up configuring an
	 * unencrypted connection while the admin expects an encrypted one.
	 */
	public function testInitializeRejectsMalformedEncryptionOptions(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
			]);
		$this->logger->expects($this->once())
			->method('error');

		$this->database->initialize($this->options(['dbdriveroptions' => '/ca.pem']));
	}

	public function testValidateRejectsMalformedEncryptionOptions(): void {
		$errors = $this->database->validate($this->options(['dbdriveroptions' => '/ca.pem']));

		$this->assertEquals([
			'The database option "dbdriveroptions" for Test has to be a list of values',
		], $errors);
	}

	public function testValidateAcceptsEncryptionOptions(): void {
		$errors = $this->database->validate($this->options([
			'dbdriveroptions' => [self::MYSQL_ATTR_SSL_CA => '/ca.pem'],
			// not an option of this database, so it is not validated either
			'pgsql_ssl' => 'verify-full',
		]));

		$this->assertEquals([], $errors);
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
