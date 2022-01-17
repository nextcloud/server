<?php
/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\DB;

use OC\DB\ConnectionFactory;
use OC\SystemConfig;
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
	public function testSplitHostFromPortAndSocket($host, array $expected) {
		/** @var SystemConfig $config */
		$config = $this->createMock(SystemConfig::class);
		$factory = new ConnectionFactory($config);

		$this->assertEquals($expected, self::invokePrivate($factory, 'splitHostFromPortAndSocket', [$host]));
	}
}
