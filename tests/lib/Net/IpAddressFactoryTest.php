<?php
/**
 * @copyright Copyright (c) 2018, Oliver Wegner (void1976@gmail.com)
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

use OC\Net\IpAddressFactory;
use OC\Net\IIpAddress;
use OC\Net\IpAddressV4;
use OC\Net\IpAddressV6;

class IpAddressFactoryTest extends \Test\TestCase {
	public function testNewForIpv4Address() {
		$ipaddress = IpAddressFactory::new('192.168.11.22');

		$this->assertTrue($ipaddress instanceof IIpAddress);
		$this->assertTrue($ipaddress instanceof IpAddressV4);
	}

	public function testNewForIpv6Address() {
		$ipaddress = IpAddressFactory::new('2001:db8:85a3:8d3:1319:8a2e:370:7348');

		$this->assertTrue($ipaddress instanceof IIpAddress);
		$this->assertTrue($ipaddress instanceof IpAddressV6);
	}

	public function testNewForIpv6AddressAbbreviated() {
		$ipaddress = IpAddressFactory::new('2001:db8:85a3:8d3:1319:8a2e::7348');

		$this->assertTrue($ipaddress instanceof IIpAddress);
		$this->assertTrue($ipaddress instanceof IpAddressV6);
	}

	public function testNewForIpv6AddressLocalhost() {
		$ipaddress = IpAddressFactory::new('::1');

		$this->assertTrue($ipaddress instanceof IIpAddress);
		$this->assertTrue($ipaddress instanceof IpAddressV6);
	}
}

