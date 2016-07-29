<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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
namespace Test\Security\Bruteforce;

use OC\AppFramework\Utility\TimeFactory;
use OC\Security\Bruteforce\Throttler;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use Test\TestCase;

/**
 * Based on the unit tests from Paragonie's Airship CMS
 * Ref: https://github.com/paragonie/airship/blob/7e5bad7e3c0fbbf324c11f963fd1f80e59762606/test/unit/Engine/Security/AirBrakeTest.php
 *
 * @package Test\Security\Bruteforce
 */
class ThrottlerTest extends TestCase {
	/** @var Throttler */
	private $throttler;
	/** @var IDBConnection */
	private $dbConnection;
	/** @var ILogger */
	private $logger;
	/** @var IConfig */
	private $config;

	public function setUp() {
		$this->dbConnection = $this->getMock('\OCP\IDBConnection');
		$this->logger = $this->getMock('\OCP\ILogger');
		$this->config = $this->getMock('\OCP\IConfig');

		$this->throttler = new Throttler(
			$this->dbConnection,
			new TimeFactory(),
			$this->logger,
			$this->config
		);
		return parent::setUp();
	}

	public function testCutoff() {
		// precisely 31 second shy of 12 hours
		$cutoff = $this->invokePrivate($this->throttler, 'getCutoff', [43169]);
		$this->assertSame(0, $cutoff->y);
		$this->assertSame(0, $cutoff->m);
		$this->assertSame(0, $cutoff->d);
		$this->assertSame(11, $cutoff->h);
		$this->assertSame(59, $cutoff->i);
		$this->assertSame(29, $cutoff->s);
		$cutoff = $this->invokePrivate($this->throttler, 'getCutoff', [86401]);
		$this->assertSame(0, $cutoff->y);
		$this->assertSame(0, $cutoff->m);
		$this->assertSame(1, $cutoff->d);
		$this->assertSame(0, $cutoff->h);
		$this->assertSame(0, $cutoff->i);
		// Leap second tolerance:
		$this->assertLessThan(2, $cutoff->s);
	}

	public function testSubnet() {
		// IPv4
		$this->assertSame(
			'64.233.191.254/32',
			$this->invokePrivate($this->throttler, 'getIPv4Subnet', ['64.233.191.254', 32])
		);
		$this->assertSame(
			'64.233.191.252/30',
			$this->invokePrivate($this->throttler, 'getIPv4Subnet', ['64.233.191.254', 30])
		);
		$this->assertSame(
			'64.233.191.240/28',
			$this->invokePrivate($this->throttler, 'getIPv4Subnet', ['64.233.191.254', 28])
		);
		$this->assertSame(
			'64.233.191.0/24',
			$this->invokePrivate($this->throttler, 'getIPv4Subnet', ['64.233.191.254', 24])
		);
		$this->assertSame(
			'64.233.188.0/22',
			$this->invokePrivate($this->throttler, 'getIPv4Subnet', ['64.233.191.254', 22])
		);
		// IPv6
		$this->assertSame(
			'2001:db8:85a3::8a2e:370:7334/127',
			$this->invokePrivate($this->throttler, 'getIPv6Subnet', ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', 127])
		);
		$this->assertSame(
			'2001:db8:85a3::8a2e:370:7300/120',
			$this->invokePrivate($this->throttler, 'getIPv6Subnet', ['2001:0db8:85a3:0000:0000:8a2e:0370:7300', 120])
		);
		$this->assertSame(
			'2001:db8:85a3::/64',
			$this->invokePrivate($this->throttler, 'getIPv6Subnet', ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', 64])
		);
		$this->assertSame(
			'2001:db8:85a3::/48',
			$this->invokePrivate($this->throttler, 'getIPv6Subnet', ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', 48])
		);
		$this->assertSame(
			'2001:db8:8500::/40',
			$this->invokePrivate($this->throttler, 'getIPv6Subnet', ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', 40])
		);
	}
}
