<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

namespace Test\Security\Normalizer;

use OC\Security\Normalizer\IpAddress;
use Test\TestCase;

class IpAddressTest extends TestCase {
	public function subnetDataProvider() {
		return [
			[
				'64.233.191.254',
				'64.233.191.254/32',
			],
			[
				'192.168.0.123',
				'192.168.0.123/32',
			],
			[
				'2001:0db8:85a3:0000:0000:8a2e:0370:7334',
				'2001:db8:85a3::8a2e:370:7334/128',
			],
			[
				'[::1]',
				'::1/128',
			],
		];
	}

	/**
	 * @dataProvider subnetDataProvider
	 *
	 * @param string $input
	 * @param string $expected
	 */
	public function testGetSubnet($input, $expected) {
		$this->assertSame($expected, (new IpAddress($input))->getSubnet());
	}

	public function testToString() {
		$this->assertSame('127.0.0.1', (string)(new IpAddress('127.0.0.1')));
	}
}
