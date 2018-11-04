<?php

namespace Test\Net;

use OC\Net\IpAddressFactory;
use OC\Net\IIpAddress;
use OC\Net\IpAddressV4;
use OC\Net\IpAddressV6;

class IpAddressFactoryTest extends \Test\TestCase {
	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

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

