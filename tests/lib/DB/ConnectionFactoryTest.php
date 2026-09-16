<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\DB;

use OC\DB\ConnectionFactory;
use OC\SystemConfig;
use OCP\ICacheFactory;
use Test\TestCase;

class ConnectionFactoryTest extends TestCase {
	public static function splitHostFromPortAndSocketData(): array {
		return [
			['127.0.0.1', ['host' => '127.0.0.1']],
			['db.example.org', ['host' => 'db.example.org']],
			['unix', ['host' => 'unix']],
			['[::1]', ['host' => '[::1]']],
			['127.0.0.1:3306', ['host' => '127.0.0.1', 'port' => 3306]],
			['db.example.org:3306', ['host' => 'db.example.org', 'port' => 3306]],
			['unix:3306', ['host' => 'unix', 'port' => 3306]],
			['[::1]:3306', ['host' => '[::1]', 'port' => 3306]],
			['unix:/socket', ['host' => 'unix', 'unix_socket' => '/socket']],
		];
	}

	/**
	 * @param string $host
	 * @param array $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('splitHostFromPortAndSocketData')]
	public function testSplitHostFromPortAndSocket($host, array $expected): void {
		/** @var SystemConfig $config */
		$config = $this->createMock(SystemConfig::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$factory = new ConnectionFactory($config, $cacheFactory);

		$this->assertEquals($expected, self::invokePrivate($factory, 'splitHostFromPortAndSocket', [$host]));
	}

	/**
	 * The numeric value of a PDO MySQL attribute, e.g. `SSL_CA`.
	 *
	 * The values are not stable across PHP versions, so they must never be hardcoded.
	 * Since PHP 8.5 the `PDO::MYSQL_ATTR_*` constants are deprecated in favor of
	 * `Pdo\Mysql::ATTR_*`, and either only exists with the MySQL driver installed.
	 */
	private function mysqlAttribute(string $name): int {
		if (!extension_loaded('pdo_mysql')) {
			$this->markTestSkipped('The pdo_mysql extension is required to resolve the PDO attribute values');
		}
		if (PHP_VERSION_ID >= 80500 && class_exists(\Pdo\Mysql::class)) {
			return (int)constant('Pdo\Mysql::ATTR_' . $name);
		}
		return (int)constant('PDO::MYSQL_ATTR_' . $name);
	}

	public function testMysqlSslConnection(): void {
		/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject $config */
		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')
			->willReturnCallback(function ($key, $default) {
				return match ($key) {
					'dbdriveroptions' => [
						$this->mysqlAttribute('SSL_CA') => 'rootCA.crt',
						$this->mysqlAttribute('SSL_CERT') => 'client.crt',
						$this->mysqlAttribute('SSL_KEY') => 'client.key',
						$this->mysqlAttribute('SSL_VERIFY_SERVER_CERT') => true,
					],
					'dbtype' => 'mysql',
					default => $default,
				};
			});
		$factory = new ConnectionFactory($config);

		$params = $factory->createConnectionParams();

		$this->assertEquals('pdo_mysql', $params['driver']);
		$this->assertEquals([
			$this->mysqlAttribute('FOUND_ROWS') => true,
			$this->mysqlAttribute('SSL_CA') => 'rootCA.crt',
			$this->mysqlAttribute('SSL_CERT') => 'client.crt',
			$this->mysqlAttribute('SSL_KEY') => 'client.key',
			$this->mysqlAttribute('SSL_VERIFY_SERVER_CERT') => true,
		], $params['driverOptions']);
	}

	public function testPgsqlSslConnection(): void {
		/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject $config */
		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')
			->willReturnCallback(function ($key, $default) {
				return match ($key) {
					'dbtype' => 'pgsql',
					'dbdriveroptions' => [
						1 => 'foo',
						3 => 'bar',
					],
					'pgsql_ssl' => [
						'mode' => 'verify-full',
						'cert' => 'client.crt',
						'key' => 'client.key',
						'crl' => 'client.crl',
						'rootcert' => 'rootCA.crt',
					],
					default => $default,
				};
			});
		$factory = new ConnectionFactory($config);

		$params = $factory->createConnectionParams();

		$this->assertEquals('pdo_pgsql', $params['driver']);
		$this->assertEquals('verify-full', $params['sslmode']);
		$this->assertEquals('rootCA.crt', $params['sslrootcert']);
		$this->assertEquals('client.crt', $params['sslcert']);
		$this->assertEquals('client.key', $params['sslkey']);
		$this->assertEquals('client.crl', $params['sslcrl']);
		$this->assertEquals([
			1 => 'foo',
			3 => 'bar',
		], $params['driverOptions']);
	}
}
