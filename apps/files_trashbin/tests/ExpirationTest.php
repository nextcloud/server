<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Tests;

use OCA\Files_Trashbin\Expiration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class ExpirationTest extends \Test\TestCase {
	public const SECONDS_PER_DAY = 86400; //60*60*24

	public const FAKE_TIME_NOW = 1000000;

	public static function expirationData(): array {
		$today = 100 * self::SECONDS_PER_DAY;
		$back10Days = (100 - 10) * self::SECONDS_PER_DAY;
		$back20Days = (100 - 20) * self::SECONDS_PER_DAY;
		$back30Days = (100 - 30) * self::SECONDS_PER_DAY;
		$back35Days = (100 - 35) * self::SECONDS_PER_DAY;

		// it should never happen, but who knows :/
		$ahead100Days = (100 + 100) * self::SECONDS_PER_DAY;

		return [
			// Expiration is disabled - always should return false
			[ 'disabled', $today, $back10Days, false, false],
			[ 'disabled', $today, $back10Days, true, false],
			[ 'disabled', $today, $ahead100Days, true, false],

			// Default: expire in 30 days or earlier when quota requirements are met
			[ 'auto', $today, $back10Days, false, false],
			[ 'auto', $today, $back35Days, false, false],
			[ 'auto', $today, $back10Days, true, true],
			[ 'auto', $today, $back35Days, true, true],
			[ 'auto', $today, $ahead100Days, true, true],

			// The same with 'auto'
			[ 'auto, auto', $today, $back10Days, false, false],
			[ 'auto, auto', $today, $back35Days, false, false],
			[ 'auto, auto', $today, $back10Days, true, true],
			[ 'auto, auto', $today, $back35Days, true, true],

			// Keep for 15 days but expire anytime if space needed
			[ '15, auto', $today, $back10Days, false, false],
			[ '15, auto', $today, $back20Days, false, false],
			[ '15, auto', $today, $back10Days, true, true],
			[ '15, auto', $today, $back20Days, true, true],
			[ '15, auto', $today, $ahead100Days, true, true],

			// Expire anytime if space needed, Expire all older than max
			[ 'auto, 15', $today, $back10Days, false, false],
			[ 'auto, 15', $today, $back20Days, false, true],
			[ 'auto, 15', $today, $back10Days, true, true],
			[ 'auto, 15', $today, $back20Days, true, true],
			[ 'auto, 15', $today, $ahead100Days, true, true],

			// Expire all older than max OR older than min if space needed
			[ '15, 25', $today, $back10Days, false, false],
			[ '15, 25', $today, $back20Days, false, false],
			[ '15, 25', $today, $back30Days, false, true],
			[ '15, 25', $today, $back10Days, false, false],
			[ '15, 25', $today, $back20Days, true, true],
			[ '15, 25', $today, $back30Days, true, true],
			[ '15, 25', $today, $ahead100Days, true, false],

			// Expire all older than max OR older than min if space needed
			// Max<Min case
			[ '25, 15', $today, $back10Days, false, false],
			[ '25, 15', $today, $back20Days, false, false],
			[ '25, 15', $today, $back30Days, false, true],
			[ '25, 15', $today, $back10Days, false, false],
			[ '25, 15', $today, $back20Days, true, false],
			[ '25, 15', $today, $back30Days, true, true],
			[ '25, 15', $today, $ahead100Days, true, false],
		];
	}

	/**
	 * @dataProvider expirationData
	 */
	public function testExpiration(string $retentionObligation, int $timeNow, int $timestamp, bool $quotaExceeded, bool $expectedResult): void {
		$mockedConfig = $this->getMockedConfig($retentionObligation);
		$mockedTimeFactory = $this->getMockedTimeFactory($timeNow);

		$expiration = new Expiration($mockedConfig, $mockedTimeFactory);
		$actualResult = $expiration->isExpired($timestamp, $quotaExceeded);

		$this->assertEquals($expectedResult, $actualResult);
	}


	public static function timestampTestData(): array {
		return [
			[ 'disabled', false],
			[ 'auto', false ],
			[ 'auto,auto', false ],
			[ 'auto, auto', false ],
			[ 'auto, 3',  self::FAKE_TIME_NOW - (3 * self::SECONDS_PER_DAY) ],
			[ '5, auto', false ],
			[ '3, 5', self::FAKE_TIME_NOW - (5 * self::SECONDS_PER_DAY) ],
			[ '10, 3', self::FAKE_TIME_NOW - (10 * self::SECONDS_PER_DAY) ],
		];
	}


	/**
	 * @dataProvider timestampTestData
	 */
	public function testGetMaxAgeAsTimestamp(string $configValue, bool|int $expectedMaxAgeTimestamp): void {
		$mockedConfig = $this->getMockedConfig($configValue);
		$mockedTimeFactory = $this->getMockedTimeFactory(
			self::FAKE_TIME_NOW
		);

		$expiration = new Expiration($mockedConfig, $mockedTimeFactory);
		$actualTimestamp = $expiration->getMaxAgeAsTimestamp();
		$this->assertEquals($expectedMaxAgeTimestamp, $actualTimestamp);
	}

	/**
	 * @return ITimeFactory|MockObject
	 */
	private function getMockedTimeFactory(int $time) {
		$mockedTimeFactory = $this->createMock(ITimeFactory::class);
		$mockedTimeFactory->expects($this->any())
			->method('getTime')
			->willReturn($time);

		return $mockedTimeFactory;
	}

	/**
	 * @return IConfig|MockObject
	 */
	private function getMockedConfig(string $returnValue) {
		$mockedConfig = $this->createMock(IConfig::class);
		$mockedConfig->expects($this->any())
			->method('getSystemValue')
			->willReturn($returnValue);

		return $mockedConfig;
	}
}
