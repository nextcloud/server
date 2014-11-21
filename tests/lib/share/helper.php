<?php
/**
* ownCloud
*
* @author Bjoern Schiessle
* @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

class Test_Share_Helper extends \Test\TestCase {

	public function expireDateProvider() {
		return array(
			// no default expire date, we take the users expire date
			array(array('defaultExpireDateSet' => false), 2000000000, 2000010000, 2000010000),
			// no default expire date and no user defined expire date, return false
			array(array('defaultExpireDateSet' => false), 2000000000, null, false),
			// unenforced expire data and no user defined expire date, return false (because the default is not enforced)
			array(array('defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => false), 2000000000, null, false),
			// enforced expire date and no user defined expire date, take default expire date
			array(array('defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => true), 2000000000, null, 2000086400),
			// unenforced expire date and user defined date > default expire date, take users expire date
			array(array('defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => false), 2000000000, 2000100000, 2000100000),
			// unenforced expire date and user expire date < default expire date, take users expire date
			array(array('defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => false), 2000000000, 2000010000, 2000010000),
			// enforced expire date and user expire date < default expire date, take users expire date
			array(array('defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => true), 2000000000, 2000010000, 2000010000),
			// enforced expire date and users expire date > default expire date, take default expire date
			array(array('defaultExpireDateSet' => true, 'expireAfterDays' => 1, 'enforceExpireDate' => true), 2000000000, 2000100000, 2000086400),
		);
	}

	/**
	 * @dataProvider expireDateProvider
	 */
	public function testCalculateExpireDate($defaultExpireSettings, $creationTime, $userExpireDate, $expected) {
		$result = \OC\Share\Helper::calculateExpireDate($defaultExpireSettings, $creationTime, $userExpireDate);
		$this->assertSame($expected, $result);
	}
}
