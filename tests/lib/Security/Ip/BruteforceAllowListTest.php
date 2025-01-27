<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Ip;

use OC\Security\Ip\BruteforceAllowList;
use OC\Security\Ip\Factory;
use OCP\IAppConfig;
use OCP\Security\Ip\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Based on the unit tests from Paragonie's Airship CMS
 * Ref: https://github.com/paragonie/airship/blob/7e5bad7e3c0fbbf324c11f963fd1f80e59762606/test/unit/Engine/Security/AirBrakeTest.php
 *
 * @package Test\Security\Bruteforce
 */
class BruteforceAllowListTest extends TestCase {
	/** @var IAppConfig|MockObject */
	private $appConfig;
	/** @var IFactory|MockObject */
	private $factory;
	/** @var BruteforceAllowList */
	private $allowList;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->factory = new Factory();

		$this->allowList = new BruteforceAllowList(
			$this->appConfig,
			$this->factory,
		);
	}

	public function dataIsBypassListed(): array {
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
	 * @dataProvider dataIsBypassListed
	 *
	 * @param string[] $allowList
	 */
	public function testIsBypassListed(
		string $ip,
		array $allowList,
		bool $isAllowListed,
	): void {
		$this->appConfig->method('getKeys')
			->with($this->equalTo('bruteForce'))
			->willReturn(array_keys($allowList));

		$this->appConfig->method('getValueString')
			->willReturnCallback(function ($app, $key, $default) use ($allowList) {
				if ($app !== 'bruteForce') {
					return $default;
				}
				if (isset($allowList[$key])) {
					return $allowList[$key];
				}
				return $default;
			});

		$this->assertSame(
			$isAllowListed,
			$this->allowList->isBypassListed($ip)
		);
	}
}
