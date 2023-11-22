<?php

declare(strict_types=1);

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

use OC\Security\Bruteforce\Backend\DatabaseBackend;
use OC\Security\Bruteforce\Throttler;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
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
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var LoggerInterface */
	private $logger;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	protected function setUp(): void {
		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->config = $this->createMock(IConfig::class);

		$this->throttler = new Throttler(
			$this->timeFactory,
			$this->logger,
			$this->config,
			new DatabaseBackend($this->dbConnection)
		);
		parent::setUp();
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
				'10.10.10.10',
				[
					'whitelist_0' => '10.10.10.11/31',
				],
				true,
			],
			[
				'10.10.10.10',
				[
					'whitelist_0' => '10.10.10.9/31',
				],
				false,
			],
			[
				'10.10.10.10',
				[
					'whitelist_0' => '10.10.10.15/29',
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
				'dead:beef:cafe::1111',
				[
					'whitelist_0' => 'dead:beef:cafe::1100/123',

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
	 * @param string $ip
	 * @param string[] $whitelists
	 * @param bool $isWhiteListed
	 * @param bool $enabled
	 */
	private function isIpWhiteListedHelper($ip,
										 $whitelists,
										 $isWhiteListed,
										 $enabled) {
		$this->config->method('getAppKeys')
			->with($this->equalTo('bruteForce'))
			->willReturn(array_keys($whitelists));
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('auth.bruteforce.protection.enabled', true)
			->willReturn($enabled);

		$this->config->method('getAppValue')
			->willReturnCallback(function ($app, $key, $default) use ($whitelists) {
				if ($app !== 'bruteForce') {
					return $default;
				}
				if (isset($whitelists[$key])) {
					return $whitelists[$key];
				}
				return $default;
			});

		$this->assertSame(
			($enabled === false) ? true : $isWhiteListed,
			self::invokePrivate($this->throttler, 'isBypassListed', [$ip])
		);
	}

	/**
	 * @dataProvider dataIsIPWhitelisted
	 *
	 * @param string $ip
	 * @param string[] $whitelists
	 * @param bool $isWhiteListed
	 */
	public function testIsIpWhiteListedWithEnabledProtection($ip,
															 $whitelists,
															 $isWhiteListed) {
		$this->isIpWhiteListedHelper(
			$ip,
			$whitelists,
			$isWhiteListed,
			true
		);
	}

	/**
	 * @dataProvider dataIsIPWhitelisted
	 *
	 * @param string $ip
	 * @param string[] $whitelists
	 * @param bool $isWhiteListed
	 */
	public function testIsIpWhiteListedWithDisabledProtection($ip,
															 $whitelists,
															 $isWhiteListed) {
		$this->isIpWhiteListedHelper(
			$ip,
			$whitelists,
			$isWhiteListed,
			false
		);
	}
}
