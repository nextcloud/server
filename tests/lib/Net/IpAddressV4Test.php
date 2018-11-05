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

	public function testGetMaxBitlength() {
		$ipaddress = new IpAddressV4('192.168.11.22');

		$this->assertSame(32, $ipaddress->getMaxBitlength());
	}
}

