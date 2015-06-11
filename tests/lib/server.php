<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test;

class Server extends \Test\TestCase {
	/** @var \OC\Server */
	protected $server;


	public function setUp() {
		parent::setUp();
		$this->server = new \OC\Server('');
	}

	public function dataTestQuery() {
		return [
			['NullCache', '\OC\Memcache\NullCache'],
			['NullCache', '\OC\Memcache\Cache'],
			['NullCache', '\OCP\IMemcache'],
		];
	}

	/**
	 * @dataProvider dataTestQuery
	 *
	 * @param string $serviceName
	 * @param string $instanceOf
	 */
	public function testQuery($serviceName, $instanceOf) {
		$this->assertInstanceOf($instanceOf, $this->server->query($serviceName), 'Service ' . $serviceName . ' did not return the right class');
	}
}
