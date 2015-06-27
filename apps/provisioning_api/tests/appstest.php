<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Provisioning_API\Tests;

class AppsTest extends TestCase {
	public function testGetAppInfo() {
		$result = \OCA\provisioning_API\Apps::getAppInfo(array('appid' => 'provisioning_api'));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertTrue($result->succeeded());

	}

	public function testGetAppInfoOnBadAppID() {

		$result = \OCA\provisioning_API\Apps::getAppInfo(array('appid' => 'not_provisioning_api'));
		$this->assertInstanceOf('OC_OCS_Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(\OCP\API::RESPOND_NOT_FOUND, $result->getStatusCode());

	}

	public function testGetApps() {

		$user = $this->generateUsers();
		\OC_Group::addToGroup($user, 'admin');
		self::loginAsUser($user);

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
