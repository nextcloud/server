<?php
/**
 * Copyright (c) 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_App extends PHPUnit_Framework_TestCase {

	private $oldAppConfigService;

	const TEST_USER1 = 'user1';
	const TEST_USER2 = 'user2';
	const TEST_USER3 = 'user3';
	const TEST_GROUP1 = 'group1';
	const TEST_GROUP2 = 'group2';

	function appVersionsProvider() {
		return array(
			// exact match
			array(
				'6.0.0.0',
				array(
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				),
				true
			),
			// in-between match
			array(
				'6.0.0.0',
				array(
					'requiremin' => '5.0',
					'requiremax' => '7.0',
				),
				true
			),
			// app too old
			array(
				'6.0.0.0',
				array(
					'requiremin' => '5.0',
					'requiremax' => '5.0',
				),
				false
			),
			// app too new
			array(
				'5.0.0.0',
				array(
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				),
				false
			),
			// only min specified
			array(
				'6.0.0.0',
				array(
					'requiremin' => '6.0',
				),
				true
			),
			// only min specified fail
			array(
				'5.0.0.0',
				array(
					'requiremin' => '6.0',
				),
				false
			),
			// only min specified legacy
			array(
				'6.0.0.0',
				array(
					'require' => '6.0',
				),
				true
			),
			// only min specified legacy fail
			array(
				'4.0.0.0',
				array(
					'require' => '6.0',
				),
				false
			),
			// only max specified
			array(
				'5.0.0.0',
				array(
					'requiremax' => '6.0',
				),
				true
			),
			// only max specified fail
			array(
				'7.0.0.0',
				array(
					'requiremax' => '6.0',
				),
				false
			),
			// variations of versions
			// single OC number
			array(
				'4',
				array(
					'require' => '4.0',
				),
				true
			),
			// multiple OC number 
			array(
				'4.3.1',
				array(
					'require' => '4.3',
				),
				true
			),
			// single app number 
			array(
				'4',
				array(
					'require' => '4',
				),
				true
			),
			// single app number fail
			array(
				'4.3',
				array(
					'require' => '5',
				),
				false
			),
			// complex
			array(
				'5.0.0',
				array(
					'require' => '4.5.1',
				),
				true
			),
			// complex fail
			array(
				'4.3.1',
				array(
					'require' => '4.3.2',
				),
				false
			),
			// two numbers
			array(
				'4.3.1',
				array(
					'require' => '4.4',
				),
				false
			),
			// one number fail
			array(
				'4.3.1',
				array(
					'require' => '5',
				),
				false
			),
			// pre-alpha app
			array(
				'5.0.3',
				array(
					'require' => '4.93',
				),
				true
			),
			// pre-alpha OC
			array(
				'6.90.0.2',
				array(
					'require' => '6.90',
				),
				true
			),
			// pre-alpha OC max
			array(
				'6.90.0.2',
				array(
					'requiremax' => '7',
				),
				true
			),
			// expect same major number match
			array(
				'5.0.3',
				array(
					'require' => '5',
				),
				true
			),
			// expect same major number match
			array(
				'5.0.3',
				array(
					'requiremax' => '5',
				),
				true
			),
		);
	}

	/**
	 * @dataProvider appVersionsProvider
	 */
	public function testIsAppCompatible($ocVersion, $appInfo, $expectedResult) {
		$this->assertEquals($expectedResult, OC_App::isAppCompatible($ocVersion, $appInfo));
	}

	/**
	 * Test that the isAppCompatible method also supports passing an array
	 * as $ocVersion
	 */
	public function testIsAppCompatibleWithArray() {
		$ocVersion = array(6);
		$appInfo = array(
			'requiremin' => '6',
			'requiremax' => '6',
		);
		$this->assertTrue(OC_App::isAppCompatible($ocVersion, $appInfo));
	}

	/**
	 * Tests that the app order is correct
	 */
	public function testGetEnabledAppsIsSorted() {
		$apps = \OC_App::getEnabledApps(true);
		// copy array
		$sortedApps = $apps;
		sort($sortedApps);
		// 'files' is always on top
		unset($sortedApps[array_search('files', $sortedApps)]);
		array_unshift($sortedApps, 'files');
		$this->assertEquals($sortedApps, $apps);
	}

	/**
	 * Providers for the app config values
	 */
	function appConfigValuesProvider() {
		return array(
			// logged in user1
			array(
				self::TEST_USER1,
				array(
					'files',
					'app1',
					'app3',
					'appforgroup1',
					'appforgroup12',
				),
				false
			),
			// logged in user2
			array(
				self::TEST_USER2,
				array(
					'files',
					'app1',
					'app3',
					'appforgroup12',
					'appforgroup2',
				),
				false
			),
			// logged in user3
			array(
				self::TEST_USER3,
				array(
					'files',
					'app1',
					'app3',
					'appforgroup1',
					'appforgroup12',
					'appforgroup2',
				),
				false
			),
			//  no user, returns all apps
			array(
				null,
				array(
					'files',
					'app1',
					'app3',
					'appforgroup1',
					'appforgroup12',
					'appforgroup2',
				),
				false,
			),
			//  user given, but ask for all
			array(
				self::TEST_USER1,
				array(
					'files',
					'app1',
					'app3',
					'appforgroup1',
					'appforgroup12',
					'appforgroup2',
				),
				true,
			),
		);
	}

	/**
	 * Test enabled apps
	 *
	 * @dataProvider appConfigValuesProvider
	 */
	public function testEnabledApps($user, $expectedApps, $forceAll) {
		$userManager = \OC::$server->getUserManager();
		$groupManager = \OC::$server->getGroupManager();
		$user1 = $userManager->createUser(self::TEST_USER1, self::TEST_USER1);
		$user2 = $userManager->createUser(self::TEST_USER2, self::TEST_USER2);
		$user3 = $userManager->createUser(self::TEST_USER3, self::TEST_USER3);

		$group1 = $groupManager->createGroup(self::TEST_GROUP1);
		$group1->addUser($user1);
		$group1->addUser($user3);
		$group2 = $groupManager->createGroup(self::TEST_GROUP2);
		$group2->addUser($user2);
		$group2->addUser($user3);

		\OC_User::setUserId($user);

		$appConfig = $this->getMock(
			'\OC\AppConfig',
			array('getValues'),
			array(\OC_DB::getConnection()),
			'',
			false
		);

		$appConfig->expects($this->once())
			->method('getValues')
			->will($this->returnValue(
				array(
					'app3' => 'yes',
					'app2' => 'no',
					'app1' => 'yes',
					'appforgroup1' => '["group1"]',
					'appforgroup2' => '["group2"]',
					'appforgroup12' => '["group2","group1"]',
				)
			)
		);
		$this->registerAppConfig($appConfig);

		$apps = \OC_App::getEnabledApps(true, $forceAll);
		$this->assertEquals($expectedApps, $apps);

		$this->restoreAppConfig();
		\OC_User::setUserId(null);

		$user1->delete();
		$user2->delete();
		$user3->delete();
		// clear user cache...
		$userManager->delete(self::TEST_USER1);
		$userManager->delete(self::TEST_USER2);
		$userManager->delete(self::TEST_USER3);
		$group1->delete();
		$group2->delete();
	}

	/**
	 * Register an app config mock for testing purposes.
	 * @param $appConfig app config mock
	 */
	private function registerAppConfig($appConfig) {
		$this->oldAppConfigService = \OC::$server->query('AppConfig');
		\OC::$server->registerService('AppConfig', function ($c) use ($appConfig) {
			return $appConfig;
		});
	}

	/**
	 * Restore the original app config service.
	 */
	private function restoreAppConfig() {
		$oldService = $this->oldAppConfigService;
		\OC::$server->registerService('AppConfig', function ($c) use ($oldService){
			return $oldService;
		});
	}
}

