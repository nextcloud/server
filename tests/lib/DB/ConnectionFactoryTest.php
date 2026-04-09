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

	public function testPgsqlSslConnection(): void {
		/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject $config */
		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')
			->willReturnCallback(function ($key, $default) {
				return match ($key) {
					'dbtype' => 'pgsql',
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
	}
}
