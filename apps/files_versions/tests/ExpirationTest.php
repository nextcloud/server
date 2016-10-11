<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Versions\Tests;

use \OCA\Files_Versions\Expiration;

class ExpirationTest extends \Test\TestCase {
	const SECONDS_PER_DAY = 86400; //60*60*24

	public function expirationData(){
		$today = 100*self::SECONDS_PER_DAY;
		$back10Days = (100-10)*self::SECONDS_PER_DAY;
		$back20Days = (100-20)*self::SECONDS_PER_DAY;
		$back30Days = (100-30)*self::SECONDS_PER_DAY;
		$back35Days = (100-35)*self::SECONDS_PER_DAY;

		// it should never happen, but who knows :/
		$ahead100Days = (100+100)*self::SECONDS_PER_DAY;

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
	public function testExpiration($retentionObligation, $timeNow, $timestamp, $quotaExceeded, $expectedResult){
		$mockedConfig = $this->getMockedConfig($retentionObligation);
		$mockedTimeFactory = $this->getMockedTimeFactory($timeNow);

		$expiration = new Expiration($mockedConfig, $mockedTimeFactory);
		$actualResult = $expiration->isExpired($timestamp, $quotaExceeded);

		$this->assertEquals($expectedResult, $actualResult);
	}


	public function configData(){
		return [
			[ 'disabled', null, null, null],
			[ 'auto', Expiration::NO_OBLIGATION, Expiration::NO_OBLIGATION, true ],
			[ 'auto,auto', Expiration::NO_OBLIGATION, Expiration::NO_OBLIGATION, true ],
			[ 'auto, auto', Expiration::NO_OBLIGATION, Expiration::NO_OBLIGATION, true ],
			[ 'auto, 3', Expiration::NO_OBLIGATION, 3, true ],
			[ '5, auto', 5, Expiration::NO_OBLIGATION, true ],
			[ '3, 5', 3, 5, false ],
			[ '10, 3', 10, 10, false ],
			[ 'g,a,r,b,a,g,e',  Expiration::NO_OBLIGATION, Expiration::NO_OBLIGATION, true ],
			[ '-3,8',  Expiration::NO_OBLIGATION, Expiration::NO_OBLIGATION, true ]
		];
	}


	/**
	 * @dataProvider configData
	 *
	 * @param string $configValue
	 * @param int $expectedMinAge
	 * @param int $expectedMaxAge
	 * @param bool $expectedCanPurgeToSaveSpace
	 */
	public function testParseRetentionObligation($configValue, $expectedMinAge, $expectedMaxAge, $expectedCanPurgeToSaveSpace){
		$mockedConfig = $this->getMockedConfig($configValue);
		$mockedTimeFactory = $this->getMockedTimeFactory(
				time()
		);

		$expiration = new Expiration($mockedConfig, $mockedTimeFactory);
		$this->assertAttributeEquals($expectedMinAge, 'minAge', $expiration);
		$this->assertAttributeEquals($expectedMaxAge, 'maxAge', $expiration);
		$this->assertAttributeEquals($expectedCanPurgeToSaveSpace, 'canPurgeToSaveSpace', $expiration);
	}

	/**
	 *
	 * @param int $time
	 * @return \OCP\AppFramework\Utility\ITimeFactory
	 */
	private function getMockedTimeFactory($time){
		$mockedTimeFactory = $this->getMockBuilder('\OCP\AppFramework\Utility\ITimeFactory')
				->disableOriginalConstructor()
				->setMethods(['getTime'])
				->getMock()
		;
		$mockedTimeFactory->expects($this->any())->method('getTime')->will(
				$this->returnValue($time)
		);

		return $mockedTimeFactory;
	}

	/**
	 *
	 * @param string $returnValue
	 * @return \OCP\IConfig
	 */
	private function getMockedConfig($returnValue){
		$mockedConfig = $this->getMockBuilder('\OCP\IConfig')
				->disableOriginalConstructor()
				->setMethods(
					[
						'setSystemValues',
						'setSystemValue',
						'getSystemValue',
						'getFilteredSystemValue',
						'deleteSystemValue',
						'getAppKeys',
						'setAppValue',
						'getAppValue',
						'deleteAppValue',
						'deleteAppValues',
						'setUserValue',
						'getUserValue',
						'getUserValueForUsers',
						'getUserKeys',
						'deleteUserValue',
						'deleteAllUserValues',
						'deleteAppFromAllUsers',
						'getUsersForUserValue'
					]
				)
				->getMock()
		;
		$mockedConfig->expects($this->any())->method('getSystemValue')->will(
				$this->returnValue($returnValue)
		);

		return $mockedConfig;
	}
}
