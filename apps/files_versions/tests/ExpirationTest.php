<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\Tests;

use OCA\Files_Versions\Expiration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ExpirationTest extends \Test\TestCase {
	public const SECONDS_PER_DAY = 86400; //60*60*24

	public function expirationData() {
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
	 *
	 * @param string $retentionObligation
	 * @param int $timeNow
	 * @param int $timestamp
	 * @param bool $quotaExceeded
	 * @param string $expectedResult
	 */
	public function testExpiration($retentionObligation, $timeNow, $timestamp, $quotaExceeded, $expectedResult): void {
		$mockedConfig = $this->getMockedConfig($retentionObligation);
		$mockedTimeFactory = $this->getMockedTimeFactory($timeNow);
		$mockedLogger = $this->createMock(LoggerInterface::class);

		$expiration = new Expiration($mockedConfig, $mockedTimeFactory, $mockedLogger);
		$actualResult = $expiration->isExpired($timestamp, $quotaExceeded);

		$this->assertEquals($expectedResult, $actualResult);
	}


	/**
	 * @param int $time
	 * @return ITimeFactory|MockObject
	 */
	private function getMockedTimeFactory($time) {
		$mockedTimeFactory = $this->createMock(ITimeFactory::class);
		$mockedTimeFactory->expects($this->any())
			->method('getTime')
			->willReturn($time);

		return $mockedTimeFactory;
	}

	/**
	 * @param string $returnValue
	 * @return IConfig|MockObject
	 */
	private function getMockedConfig($returnValue) {
		$mockedConfig = $this->createMock(IConfig::class);
		$mockedConfig->expects($this->any())
			->method('getSystemValue')
			->willReturn($returnValue);

		return $mockedConfig;
	}
}
