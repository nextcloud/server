<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Setup;

use OC\Setup\MySQL;
use OC\SystemConfig;
use OCP\IL10N;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class MySQLTest extends TestCase {
	private SystemConfig&MockObject $config;
	private MySQL $database;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(SystemConfig::class);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')
			->willReturnCallback(fn (string $text, array $parameters = []) => vsprintf($text, $parameters));

		$this->database = new MySQL(
			$l10n,
			$this->config,
			$this->createMock(LoggerInterface::class),
			$this->createMock(ISecureRandom::class),
		);
	}

	/**
	 * The numeric value of a PDO MySQL attribute, e.g. `SSL_CA`.
	 *
	 * The values are not stable across PHP versions, so they must never be hardcoded.
	 * Since PHP 8.5 the `PDO::MYSQL_ATTR_*` constants are deprecated in favor of
	 * `Pdo\Mysql::ATTR_*`, and either only exists with the MySQL driver installed.
	 */
	private function attribute(string $name): int {
		if (!extension_loaded('pdo_mysql')) {
			$this->markTestSkipped('The pdo_mysql extension is required to resolve the PDO attribute values');
		}
		if (PHP_VERSION_ID >= 80500 && class_exists(\Pdo\Mysql::class)) {
			return (int)constant('Pdo\Mysql::ATTR_' . $name);
		}
		return (int)constant('PDO::MYSQL_ATTR_' . $name);
	}

	/**
	 * MySQL/MariaDB is configured through PDO driver options, keyed by the numeric PDO
	 * attributes - which is why the web installer and CLI cannot pass them directly.
	 */
	public function testInitializeMapsEncryptionOptions(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
				'dbdriveroptions' => [
					$this->attribute('SSL_CA') => '/ca.pem',
					$this->attribute('SSL_CERT') => '/client.crt',
					$this->attribute('SSL_KEY') => '/client.key',
					$this->attribute('SSL_VERIFY_SERVER_CERT') => false,
				],
			]);

		$this->database->initialize($this->options([
			'dbsslca' => '/ca.pem',
			'dbsslcert' => '/client.crt',
			'dbsslkey' => '/client.key',
			'dbsslnoverify' => true,
		]));
	}

	/**
	 * Driver options provided as raw config value, e.g. through an autoconfig file, must
	 * survive - and their numeric keys must not be renumbered.
	 */
	public function testInitializeMergesWithRawDriverOptions(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
				'dbdriveroptions' => [
					$this->attribute('INIT_COMMAND') => 'SET wait_timeout = 28800',
					$this->attribute('SSL_CA') => '/ca.pem',
				],
			]);

		$this->database->initialize($this->options([
			'dbdriveroptions' => [$this->attribute('INIT_COMMAND') => 'SET wait_timeout = 28800'],
			'dbsslca' => '/ca.pem',
		]));
	}

	public function testInitializeSkipsEmptyEncryptionOptions(): void {
		$this->config->expects($this->once())
			->method('setValues')
			->with([
				'dbname' => 'nextcloud',
				'dbhost' => 'db.example.org',
				'dbtableprefix' => 'oc_',
			]);

		$this->database->initialize($this->options([
			'dbsslca' => '',
			'dbsslcert' => '',
			'dbsslkey' => '',
			'dbsslnoverify' => false,
		]));
	}

	public static function unsupportedEncryptionOptions(): array {
		return [
			// There is no PDO equivalent of the PostgreSQL sslmode
			'dbsslmode' => ['dbsslmode', 'require'],
			// A revocation list cannot be passed through PDO
			'dbsslcrl' => ['dbsslcrl', '/crl.pem'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('unsupportedEncryptionOptions')]
	public function testValidateRejectsUnsupportedEncryptionOptions(string $option, string $value): void {
		$errors = $this->database->validate($this->options([$option => $value]));

		$this->assertEquals([
			"The database option \"$option\" is not supported by MySQL/MariaDB",
		], $errors);
	}

	public function testValidateRejectsIncompleteClientCertificate(): void {
		$errors = $this->database->validate($this->options(['dbsslcert' => '/client.crt']));

		$this->assertEquals([
			'The database options "dbsslcert" and "dbsslkey" have to be provided together',
		], $errors);
	}

	public function testValidateAcceptsEncryptionOptions(): void {
		$errors = $this->database->validate($this->options([
			'dbsslca' => '/ca.pem',
			'dbsslcert' => '/client.crt',
			'dbsslkey' => '/client.key',
			'dbsslnoverify' => true,
		]));

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
