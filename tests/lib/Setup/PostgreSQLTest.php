<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Setup;

use OC\Setup\PostgreSQL;
use OC\SystemConfig;
use OCP\IL10N;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PostgreSQLTest extends TestCase {
	private const PGSQL_SSL = [
		'mode' => 'verify-full',
		'rootcert' => '/rootCA.crt',
		'cert' => '/client.crt',
		'key' => '/client.key',
	];

	private SystemConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private PostgreSQL $database;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(SystemConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')
			->willReturnCallback(fn (string $text, array $parameters = []) => vsprintf($text, $parameters));

		$this->database = new PostgreSQL(
			$l10n,
			$this->config,
			$this->logger,
			$this->createMock(ISecureRandom::class),
		);
	}

	/**
	 * PostgreSQL is configured through its own set of connection parameters instead of PDO
	 * driver options. They are only read from the system config, so they have to be
	 * persisted by initialize() - before any connection is opened by setupDatabase().
	 */
	public function testInitializePersistsPgsqlSsl(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
				'pgsql_ssl' => self::PGSQL_SSL,
			]);

		$this->database->initialize($this->options(['pgsql_ssl' => self::PGSQL_SSL]));
	}

	public static function emptyPgsqlSsl(): array {
		return [
			'not provided' => [[]],
			'empty array' => [['pgsql_ssl' => []]],
			'null' => [['pgsql_ssl' => null]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('emptyPgsqlSsl')]
	public function testInitializeSkipsEmptyPgsqlSsl(array $additional): void {
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
	public function testInitializeRejectsMalformedPgsqlSsl(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
			]);
		$this->logger->expects($this->once())
			->method('error');

		$this->database->initialize($this->options(['pgsql_ssl' => 'verify-full']));
	}

	public function testValidateRejectsMalformedPgsqlSsl(): void {
		$errors = $this->database->validate($this->options(['pgsql_ssl' => 'verify-full']));

		$this->assertEquals([
			'The database option "pgsql_ssl" for PostgreSQL has to be a list of values',
		], $errors);
	}

	public function testValidateAcceptsPgsqlSsl(): void {
		$errors = $this->database->validate($this->options(['pgsql_ssl' => self::PGSQL_SSL]));

		$this->assertEquals([], $errors);
	}

	private function options(array $additional = []): array {
		return array_merge([
			'dbuser' => 'admin',
			'dbpass' => 'admin-password',
			'dbname' => 'nextcloud',
			'dbhost' => 'db.example.org',
		], $additional);
	}
}
