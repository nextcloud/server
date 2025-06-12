<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Share;

use OC\Share\Helper;

/**
 * @group DB
 * Class Helper
 */
class HelperTest extends \Test\TestCase {
	public static function expireDateProvider(): array {
		return [
			// no default expire date, we take the users expire date
			[['defaultExpireDateSet' => false], 2000000000, 2000010000, 2000010000],
			// no default expire date and no user defined expire date, return false
			[['defaultExpireDateSet' => false], 2000000000, null, false],
			// unenforced expire data and no user defined expire date, return false (because the default is not enforced)
			[['defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => false], 2000000000, null, false],
			// enforced expire date and no user defined expire date, take default expire date
			[['defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => true], 2000000000, null, 2000086400],
			// unenforced expire date and user defined date > default expire date, take users expire date
			[['defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => false], 2000000000, 2000100000, 2000100000],
			// unenforced expire date and user expire date < default expire date, take users expire date
			[['defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => false], 2000000000, 2000010000, 2000010000],
			// enforced expire date and user expire date < default expire date, take users expire date
			[['defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => true], 2000000000, 2000010000, 2000010000],
			// enforced expire date and users expire date > default expire date, take default expire date
			[['defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => true], 2000000000, 2000100000, 2000086400],
		];
	}

	/**
	 * @dataProvider expireDateProvider
	 */
	public function testCalculateExpireDate($defaultExpireSettings, $creationTime, $userExpireDate, $expected): void {
		$result = Helper::calculateExpireDate($defaultExpireSettings, $creationTime, $userExpireDate);
		$this->assertSame($expected, $result);
	}

	/**
	 * @dataProvider dataTestCompareServerAddresses
	 *
	 * @param string $server1
	 * @param string $server2
	 * @param bool $expected
	 */
	public function testIsSameUserOnSameServer($user1, $server1, $user2, $server2, $expected): void {
		$this->assertSame($expected,
			Helper::isSameUserOnSameServer($user1, $server1, $user2, $server2)
		);
	}

	public static function dataTestCompareServerAddresses(): array {
		return [
			['user1', 'http://server1', 'user1', 'http://server1', true],
			['user1', 'https://server1', 'user1', 'http://server1', true],
			['user1', 'http://serVer1', 'user1', 'http://server1', true],
			['user1', 'http://server1/',  'user1', 'http://server1', true],
			['user1', 'server1', 'user1', 'http://server1', true],
			['user1', 'http://server1', 'user1', 'http://server2', false],
			['user1', 'https://server1', 'user1', 'http://server2', false],
			['user1', 'http://serVer1', 'user1', 'http://serer2', false],
			['user1', 'http://server1/', 'user1', 'http://server2', false],
			['user1', 'server1', 'user1', 'http://server2', false],
			['user1', 'http://server1', 'user2', 'http://server1', false],
			['user1', 'https://server1', 'user2', 'http://server1', false],
			['user1', 'http://serVer1', 'user2', 'http://server1', false],
			['user1', 'http://server1/',  'user2', 'http://server1', false],
			['user1', 'server1', 'user2', 'http://server1', false],
		];
	}
}
