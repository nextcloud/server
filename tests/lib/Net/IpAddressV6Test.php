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
use OC\Net\IpAddressV6;

class IpAddressV6Test extends \Test\TestCase {
	public function testIsRangeAddress() {
		$ipaddress = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e:370:7348');

		$this->assertFalse($ipaddress->isRange());
	}

	public function testIsRangeLocalhost() {
		$ipaddress = new IpAddressV6('::1');

		$this->assertFalse($ipaddress->isRange());
	}

	public function testIsRangeRangeSome() {
		$ipaddress = new IpAddressV6('2001:db8:85a3:8d3:1319::/80');

		$this->assertTrue($ipaddress->isRange());
	}

	public function testIsRangeRangeAll() {
		$ipaddress = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e:370:7348/128');

		$this->assertFalse($ipaddress->isRange());
	}

	public function testIsRangeRangeNone() {
		$ipaddress = new IpAddressV6('::/0');

		$this->assertTrue($ipaddress->isRange());
	}

	public function testContainsAddressSingleMatch() {
		$ip1 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e:370:7348');
		$ip2 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e:370:7348');

		$this->assertTrue($ip1->containsAddress($ip2));
	}

	public function testContainsAddressSingleNoMatch() {
		$ip1 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e:370:7348');
		$ip2 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e:370:7349');

		$this->assertFalse($ip1->containsAddress($ip2));
	}

	public function testContainsAddressRangeMatch() {
		$ip1 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e::/96');
		$ip2 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e:370:7348');

		$this->assertTrue($ip1->containsAddress($ip2));
	}

	public function testContainsAddressRangeNoMatch() {
		$ip1 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2e::/96');
		$ip2 = new IpAddressV6('2001:db8:85a3:8d3:1319:8a2f:370:7348');

		$this->assertFalse($ip1->containsAddress($ip2));
	}
}

