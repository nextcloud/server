<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Provisioning_API\Tests\Controller;

use OCA\Provisioning_API\Controller\AppsController;
use OCA\Provisioning_API\Tests\TestCase;
use OCP\App\IAppManager;
use OCP\AppFramework\OCS\OCSException;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Class AppsTest
 *
 * @group DB
 *
 * @package OCA\Provisioning_API\Tests
 */
class AppsControllerTest extends TestCase {
	/** @var IAppManager */
	private $appManager;
	/** @var AppsController */
	private $api;
	/** @var IUserSession */
	private $userSession;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = \OC::$server->getAppManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->userSession = \OC::$server->getUserSession();

		$request = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();

		$this->api = new AppsController(
			'provisioning_api',
			$request,
			$this->appManager
		);
	}

	protected function tearDown(): void {
		$this->userSession->setUser(null);
	}

	public function testGetAppInfo(): void {
		$result = $this->api->getAppInfo('provisioning_api');
		$expected = $this->appManager->getAppInfo('provisioning_api');
		$this->assertEquals($expected, $result->getData());
	}


	public function testGetAppInfoOnBadAppID(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(998);

		$this->api->getAppInfo('not_provisioning_api');
	}

	public function testGetApps(): void {
		$user = $this->generateUsers();
		$this->groupManager->get('admin')->addUser($user);
		$this->userSession->setUser($user);

		$result = $this->api->getApps();

		$data = $result->getData();
		$this->assertEquals(count((new \OC_App())->listAllApps()), count($data['apps']));
	}

	public function testGetAppsEnabled(): void {
		$result = $this->api->getApps('enabled');
		$data = $result->getData();
		$this->assertEquals(count(\OC_App::getEnabledApps()), count($data['apps']));
	}

	public function testGetAppsDisabled(): void {
		$result = $this->api->getApps('disabled');
		$data = $result->getData();
		$apps = (new \OC_App)->listAllApps();
		$list = [];
		foreach ($apps as $app) {
			$list[] = $app['id'];
		}
		$disabled = array_diff($list, \OC_App::getEnabledApps());
		$this->assertEquals(count($disabled), count($data['apps']));
	}

	
	public function testGetAppsInvalidFilter(): void {
		$this->expectException(OCSException::class);
		$this->expectExceptionCode(101);

		$this->api->getApps('foo');
	}
}
