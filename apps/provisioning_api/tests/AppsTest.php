<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
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

namespace OCA\Provisioning_API\Tests;


use OC\OCSClient;
use OCA\Provisioning_API\Apps;
use OCP\API;
use OCP\App\IAppManager;
use OCP\IUserSession;

/**
 * Class AppsTest
 *
 * @group DB
 *
 * @package OCA\Provisioning_API\Tests
 */
class AppsTest extends TestCase {
	/** @var IAppManager */
	private $appManager;
	/** @var Apps */
	private $api;
	/** @var IUserSession */
	private $userSession;
	/** @var OCSClient|\PHPUnit_Framework_MockObject_MockObject */
	private $ocsClient;

	protected function setUp() {
		parent::setUp();

		$this->appManager = \OC::$server->getAppManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->userSession = \OC::$server->getUserSession();
		$this->ocsClient = $this->getMockBuilder('OC\OCSClient')
			->disableOriginalConstructor()
			->getMock();

		$this->api = new Apps($this->appManager, $this->ocsClient);
	}

	public function testGetAppInfo() {
		$result = $this->api->getAppInfo(['appid' => 'provisioning_api']);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertTrue($result->succeeded());
	}

	public function testGetAppInfoOnBadAppID() {
		$result = $this->api->getAppInfo(['appid' => 'not_provisioning_api']);
		$this->assertInstanceOf('\OC\OCS\Result', $result);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(API::RESPOND_NOT_FOUND, $result->getStatusCode());
	}

	public function testGetApps() {
		$this->ocsClient
				->expects($this->any())
				->method($this->anything())
				->will($this->returnValue(null));
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);

		$result = $this->api->getApps([]);

		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(count(\OC_App::listAllApps(false, true, $this->ocsClient)), count($data['apps']));
	}

	public function testGetAppsEnabled() {
		$_GET['filter'] = 'enabled';
		$result = $this->api->getApps(['filter' => 'enabled']);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$this->assertEquals(count(\OC_App::getEnabledApps()), count($data['apps']));
	}

	public function testGetAppsDisabled() {
		$this->ocsClient
				->expects($this->any())
				->method($this->anything())
				->will($this->returnValue(null));
		$_GET['filter'] = 'disabled';
		$result = $this->api->getApps(['filter' => 'disabled']);
		$this->assertTrue($result->succeeded());
		$data = $result->getData();
		$apps = \OC_App::listAllApps(false, true, $this->ocsClient);
		$list =  array();
		foreach($apps as $app) {
			$list[] = $app['id'];
		}
		$disabled = array_diff($list, \OC_App::getEnabledApps());
		$this->assertEquals(count($disabled), count($data['apps']));
	}

	public function testGetAppsInvalidFilter() {
		$_GET['filter'] = 'foo';
		$result = $this->api->getApps([]);
		$this->assertFalse($result->succeeded());
		$this->assertEquals(101, $result->getStatusCode());
	}
}
