<?php
/**
 * Copyright (c) 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\App\InfoParser;
use OC\AppConfig;
use OCP\IAppConfig;

/**
 * Class AppTest
 *
 * @group DB
 */
class AppTest extends \Test\TestCase {

	const TEST_USER1 = 'user1';
	const TEST_USER2 = 'user2';
	const TEST_USER3 = 'user3';
	const TEST_GROUP1 = 'group1';
	const TEST_GROUP2 = 'group2';

	public function appVersionsProvider() {
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
			// dependencies versions before require*
			array(
				'6.0.0.0',
				array(
					'requiremin' => '5.0',
					'requiremax' => '7.0',
					'dependencies' => array(
						'owncloud' => array(
							'@attributes' => array(
								'min-version' => '7.0',
								'max-version' => '7.0',
							),
						),
					),
				),
				false
			),
			array(
				'6.0.0.0',
				array(
					'requiremin' => '5.0',
					'requiremax' => '7.0',
					'dependencies' => array(
						'owncloud' => array(
							'@attributes' => array(
								'min-version' => '5.0',
								'max-version' => '5.0',
							),
						),
					),
				),
				false
			),
			array(
				'6.0.0.0',
				array(
					'requiremin' => '5.0',
					'requiremax' => '5.0',
					'dependencies' => array(
						'owncloud' => array(
							'@attributes' => array(
								'min-version' => '5.0',
								'max-version' => '7.0',
							),
						),
					),
				),
				true
			),
			[
				'9.2.0.0',
				[
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '9.0',
								'max-version' => '9.1',
							],
						],
						'nextcloud' => [
							'@attributes' => [
								'min-version' => '9.1',
								'max-version' => '9.2',
							],
						],
					],
				],
				true
			],
			[
				'9.2.0.0',
				[
					'dependencies' => [
						'nextcloud' => [
							'@attributes' => [
								'min-version' => '9.1',
								'max-version' => '9.2',
							],
						],
					],
				],
				true
			],
		);
	}

	/**
	 * @dataProvider appVersionsProvider
	 */
	public function testIsAppCompatible($ocVersion, $appInfo, $expectedResult) {
		$this->assertEquals($expectedResult, \OC_App::isAppCompatible($ocVersion, $appInfo));
	}

	/**
	 * Tests that the app order is correct
	 */
	public function testGetEnabledAppsIsSorted() {
		$apps = \OC_App::getEnabledApps();
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
	public function appConfigValuesProvider() {
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
					'cloud_federation_api',
					'dav',
					'federatedfilesharing',
					'lookup_server_connector',
					'oauth2',
					'provisioning_api',
					'settings',
					'twofactor_backupcodes',
					'workflowengine',
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
					'cloud_federation_api',
					'dav',
					'federatedfilesharing',
					'lookup_server_connector',
					'oauth2',
					'provisioning_api',
					'settings',
					'twofactor_backupcodes',
					'workflowengine',
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
					'cloud_federation_api',
					'dav',
					'federatedfilesharing',
					'lookup_server_connector',
					'oauth2',
					'provisioning_api',
					'settings',
					'twofactor_backupcodes',
					'workflowengine',
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
					'cloud_federation_api',
					'dav',
					'federatedfilesharing',
					'lookup_server_connector',
					'oauth2',
					'provisioning_api',
					'settings',
					'twofactor_backupcodes',
					'workflowengine',
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
					'cloud_federation_api',
					'dav',
					'federatedfilesharing',
					'lookup_server_connector',
					'oauth2',
					'provisioning_api',
					'settings',
					'twofactor_backupcodes',
					'workflowengine',
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

		$this->setupAppConfigMock()->expects($this->once())
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

		$apps = \OC_App::getEnabledApps(false, $forceAll);

		$this->restoreAppConfig();
		\OC_User::setUserId(null);

		$user1->delete();
		$user2->delete();
		$user3->delete();

		$group1->delete();
		$group2->delete();

		$this->assertEquals($expectedApps, $apps);
	}

	/**
	 * Test isEnabledApps() with cache, not re-reading the list of
	 * enabled apps more than once when a user is set.
	 */
	public function testEnabledAppsCache() {
		$userManager = \OC::$server->getUserManager();
		$user1 = $userManager->createUser(self::TEST_USER1, self::TEST_USER1);

		\OC_User::setUserId(self::TEST_USER1);

		$this->setupAppConfigMock()->expects($this->once())
			->method('getValues')
			->will($this->returnValue(
				array(
					'app3' => 'yes',
					'app2' => 'no',
				)
			)
			);

		$apps = \OC_App::getEnabledApps();
		$this->assertEquals(array('files', 'app3', 'cloud_federation_api', 'dav', 'federatedfilesharing', 'lookup_server_connector', 'oauth2', 'provisioning_api', 'settings', 'twofactor_backupcodes', 'workflowengine'), $apps);

		// mock should not be called again here
		$apps = \OC_App::getEnabledApps();
		$this->assertEquals(array('files', 'app3', 'cloud_federation_api', 'dav', 'federatedfilesharing', 'lookup_server_connector', 'oauth2', 'provisioning_api', 'settings', 'twofactor_backupcodes', 'workflowengine'), $apps);

		$this->restoreAppConfig();
		\OC_User::setUserId(null);

		$user1->delete();
	}


	private function setupAppConfigMock() {
		$appConfig = $this->getMockBuilder(AppConfig::class)
			->setMethods(['getValues'])
			->setConstructorArgs([\OC::$server->getDatabaseConnection()])
			->disableOriginalConstructor()
			->getMock();

		$this->registerAppConfig($appConfig);
		return $appConfig;
	}

	/**
	 * Register an app config mock for testing purposes.
	 *
	 * @param IAppConfig $appConfig app config mock
	 */
	private function registerAppConfig(AppConfig $appConfig) {
		$this->overwriteService('AppConfig', $appConfig);
		$this->overwriteService('AppManager', new \OC\App\AppManager(
			\OC::$server->getUserSession(),
			$appConfig,
			\OC::$server->getGroupManager(),
			\OC::$server->getMemCacheFactory(),
			\OC::$server->getEventDispatcher(),
			\OC::$server->getLogger()
		));
	}

	/**
	 * Restore the original app config service.
	 */
	private function restoreAppConfig() {
		$this->restoreService('AppConfig');
		$this->restoreService('AppManager');

		// Remove the cache of the mocked apps list with a forceRefresh
		\OC_App::getEnabledApps();
	}

	/**
	 * Providers for the app data values
	 */
	public function appDataProvider() {
		return [
			[
				['description' => " \t  This is a multiline \n test with \n \t \n \n some new lines   "],
				['description' => "This is a multiline \n test with \n \t \n \n some new lines"],
			],
			[
				['description' => " \t  This is a multiline \n test with \n \t   some new lines   "],
				['description' => "This is a multiline \n test with \n \t   some new lines"],
			],
			[
				['description' => hex2bin('5065726d657420646520732761757468656e7469666965722064616e732070697769676f20646972656374656d656e74206176656320736573206964656e74696669616e7473206f776e636c6f75642073616e73206c65732072657461706572206574206d657420c3a0206a6f757273206365757820636920656e20636173206465206368616e67656d656e74206465206d6f742064652070617373652e0d0a0d')],
				['description' => "Permet de s'authentifier dans piwigo directement avec ses identifiants owncloud sans les retaper et met à jours ceux ci en cas de changement de mot de passe."],
			],
			[
				['not-a-description' => " \t  This is a multiline \n test with \n \t   some new lines   "],
				[
					'not-a-description' => " \t  This is a multiline \n test with \n \t   some new lines   ",
					'description' => '',
				],
			],
			[
				['description' => [100, 'bla']],
				['description' => ''],
			],
		];
	}

	/**
	 * Test app info parser
	 *
	 * @dataProvider appDataProvider
	 * @param array $data
	 * @param array $expected
	 */
	public function testParseAppInfo(array $data, array $expected) {
		$this->assertSame($expected, \OC_App::parseAppInfo($data));
	}

	public function testParseAppInfoL10N() {
		$parser = new InfoParser();
		$data = $parser->parse(\OC::$SERVERROOT. "/tests/data/app/description-multi-lang.xml");
		$this->assertEquals('English', \OC_App::parseAppInfo($data, 'en')['description']);
		$this->assertEquals('German', \OC_App::parseAppInfo($data, 'de')['description']);
	}

	public function testParseAppInfoL10NSingleLanguage() {
		$parser = new InfoParser();
		$data = $parser->parse(\OC::$SERVERROOT. "/tests/data/app/description-single-lang.xml");
		$this->assertEquals('English', \OC_App::parseAppInfo($data, 'en')['description']);
	}
}

