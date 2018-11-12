<?php

namespace Test\Net;

use OC\Net\IpAddressV4;
use OC\Net\IIpAddress;

class IpAddressV4Test extends \Test\TestCase {
	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testIsRangeSingle() {
		$ipaddress = new IpAddressV4('192.168.11.22');

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

