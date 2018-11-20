<?php
/**
 * @copyright Copyright (c) 2018, Oliver Wegner (void1976@gmail.com)
 *
 * @author Oliver Wegner <void1976@gmail.com>
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

namespace Test\Net;

use OCP\Net\IIpAddress;
use OC\Net\IpAddressV4;

class IpAddressV4Test extends \Test\TestCase {
	public function testIsRangeAddress() {
		$ipaddress = new IpAddressV4('192.168.11.22');

		$this->assertFalse($ipaddress->isRange());
	}

	public function testIsRangeLocalhost() {
		$ipaddress = new IpAddressV4('127.0.0.1');

		$this->assertFalse($ipaddress->isRange());
	}

	public function testIsRangeRangeSome() {
		$ipaddress = new IpAddressV4('192.168.11.0/24');

		$this->assertTrue($ipaddress->isRange());
	}

	public function testIsRangeRangeAll() {
		$ipaddress = new IpAddressV4('192.168.11.22/32');

		$this->assertFalse($ipaddress->isRange());
	}

	public function testIsRangeRangeNone() {
		$ipaddress = new IpAddressV4('0.0.0.0/0');

		$this->assertTrue($ipaddress->isRange());
	}

	public function testContainsAddressSingleMatch() {
		$ip1 = new IpAddressV4('192.168.11.22');
		$ip2 = new IpAddressV4('192.168.11.22');

		$this->assertTrue($ip1->containsAddress($ip2));
	}

	public function testContainsAddressSingleNoMatch() {
		$ip1 = new IpAddressV4('192.168.11.22');
		$ip2 = new IpAddressV4('192.168.11.23');

		$this->assertFalse($ip1->containsAddress($ip2));
	}

	public function testContainsAddressRangeMatch() {
		$ip1 = new IpAddressV4('192.168.11.0/24');
		$ip2 = new IpAddressV4('192.168.11.23');

		$this->assertTrue($ip1->containsAddress($ip2));
	}

	public function testContainsAddressRangeNoMatch() {
		$ip1 = new IpAddressV4('192.168.11.0/24');
		$ip2 = new IpAddressV4('192.168.12.23');

		$this->assertFalse($ip1->containsAddress($ip2));
	}
}

