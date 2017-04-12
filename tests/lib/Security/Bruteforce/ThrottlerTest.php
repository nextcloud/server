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
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	public function setUp() {
		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);

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

	public function dataIsIPWhitelisted() {
		return [
			[
				'10.10.10.10',
				[
					'whitelist_0' => '10.10.10.0/24',
				],
				true,
			],
			[
				'10.10.10.10',
				[
					'whitelist_0' => '192.168.0.0/16',
				],
				false,
			],
			[
				'10.10.10.10',
				[
					'whitelist_0' => '192.168.0.0/16',
					'whitelist_1' => '10.10.10.0/24',
				],
				true,
			],
			[
				'dead:beef:cafe::1',
				[
					'whitelist_0' => '192.168.0.0/16',
					'whitelist_1' => '10.10.10.0/24',
					'whitelist_2' => 'deaf:beef:cafe:1234::/64'
				],
				false,
			],
			[
				'dead:beef:cafe::1',
				[
					'whitelist_0' => '192.168.0.0/16',
					'whitelist_1' => '10.10.10.0/24',
					'whitelist_2' => 'deaf:beef::/64'
				],
				false,
			],
			[
				'dead:beef:cafe::1',
				[
					'whitelist_0' => '192.168.0.0/16',
					'whitelist_1' => '10.10.10.0/24',
					'whitelist_2' => 'deaf:cafe::/8'
				],
				true,
			],
			[
				'invalid',
				[],
				false,
			],
		];
	}

	/**
	 * @dataProvider dataIsIPWhitelisted
	 *
	 * @param string $ip
	 * @param string[] $whitelists
	 * @param bool $isWhiteListed
	 */
	public function testIsIPWhitelisted($ip, $whitelists, $isWhiteListed) {
		$this->config->method('getAppKeys')
			->with($this->equalTo('bruteForce'))
			->willReturn(array_keys($whitelists));

		$this->config->method('getAppValue')
			->will($this->returnCallback(function($app, $key, $default) use ($whitelists) {
				if ($app !== 'bruteForce') {
					return $default;
				}
				if (isset($whitelists[$key])) {
					return $whitelists[$key];
				}
				return $default;
			}));

		$this->assertSame(
			$isWhiteListed,
			$this->invokePrivate($this->throttler, 'isIPWhitelisted', [$ip])
		);
	}
}
