<?php

namespace Test\Net;

use OC\Net\IpAddressV6;
use OC\Net\IIpAddress;

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

