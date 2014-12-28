<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Tom <tom@owncloud.com>
 * @author Thomas Müller <deepdiver@owncloud.com>
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
 *
 */

class Test_Provisioning_Api_Apps extends PHPUnit_Framework_TestCase {

	private $users = array();

	/**
	 * Generates a temp user
	 * @param $num int number of users to generate
	 */
	function generateUsers($num=1) {
		for($i=0; $i<$num; $i++) {
			$user = uniqid();
			\OC_User::createUser($user, 'password');
			$this->users[] = $user;
			$users[] = $user;
		}
		return count($users) == 1 ? reset($users) : $users;
	}

	function testGetAppInfo() {

		$result = \OCA\provisioning_API\Apps::getAppInfo(array('appid' => 'provisioning_api'));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());

	}

	function testGetAppInfoOnBadAppID() {

		$result = \OCA\provisioning_API\Apps::getAppInfo(array('appid' => 'not_provisioning_api'));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OC_API::RESPOND_NOT_FOUND, $result->getStatusCode());

	}

	public function testGetApps() {

		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		\OC_User::setUserId($user);

		$result = \OCA\provisioning_API\Apps::getApps(array());

		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(count(\OC_App::listAllApps()), count($data['apps']));

	}

	public function testGetAppsEnabled() {

		$_GET['filter'] = 'enabled';
		$result = \OCA\provisioning_API\Apps::getApps(array('filter' => 'enabled'));
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(count(\OC_App::getEnabledApps()), count($data['apps']));

	}

	public function testGetAppsDisabled() {

		$_GET['filter'] = 'disabled';
		$result = \OCA\provisioning_API\Apps::getApps(array('filter' => 'disabled'));
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$apps = \OC_App::listAllApps();
		$list =  array();
		foreach($apps as $app) {
			$list[] = $app['id'];
		}
		$disabled = array_diff($list, \OC_App::getEnabledApps());
		$this->assertEquals(count($disabled), count($data['apps']));

	}

}
