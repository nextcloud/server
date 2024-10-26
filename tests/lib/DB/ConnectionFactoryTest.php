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
	public function splitHostFromPortAndSocketData() {
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
	 * @dataProvider splitHostFromPortAndSocketData
	 * @param string $host
	 * @param array $expected
	 */
	public function testSplitHostFromPortAndSocket($host, array $expected): void {
		/** @var SystemConfig $config */
		$config = $this->createMock(SystemConfig::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$factory = new ConnectionFactory($config, $cacheFactory);

		$this->assertEquals($expected, self::invokePrivate($factory, 'splitHostFromPortAndSocket', [$host]));
	}
}
