<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\App\AppManager;
use OC\App\InfoParser;
use OC\AppConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class AppTest
 *
 * @group DB
 */
class AppTest extends \Test\TestCase {
	public const TEST_USER1 = 'user1';
	public const TEST_USER2 = 'user2';
	public const TEST_USER3 = 'user3';
	public const TEST_GROUP1 = 'group1';
	public const TEST_GROUP2 = 'group2';

	public static function appVersionsProvider(): array {
		return [
			// exact match
			[
				'6.0.0.0',
				[
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				],
				true
			],
			// in-between match
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '7.0',
				],
				true
			],
			// app too old
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '5.0',
				],
				false
			],
			// app too new
			[
				'5.0.0.0',
				[
					'requiremin' => '6.0',
					'requiremax' => '6.0',
				],
				false
			],
			// only min specified
			[
				'6.0.0.0',
				[
					'requiremin' => '6.0',
				],
				true
			],
			// only min specified fail
			[
				'5.0.0.0',
				[
					'requiremin' => '6.0',
				],
				false
			],
			// only min specified legacy
			[
				'6.0.0.0',
				[
					'require' => '6.0',
				],
				true
			],
			// only min specified legacy fail
			[
				'4.0.0.0',
				[
					'require' => '6.0',
				],
				false
			],
			// only max specified
			[
				'5.0.0.0',
				[
					'requiremax' => '6.0',
				],
				true
			],
			// only max specified fail
			[
				'7.0.0.0',
				[
					'requiremax' => '6.0',
				],
				false
			],
			// variations of versions
			// single OC number
			[
				'4',
				[
					'require' => '4.0',
				],
				true
			],
			// multiple OC number
			[
				'4.3.1',
				[
					'require' => '4.3',
				],
				true
			],
			// single app number
			[
				'4',
				[
					'require' => '4',
				],
				true
			],
			// single app number fail
			[
				'4.3',
				[
					'require' => '5',
				],
				false
			],
			// complex
			[
				'5.0.0',
				[
					'require' => '4.5.1',
				],
				true
			],
			// complex fail
			[
				'4.3.1',
				[
					'require' => '4.3.2',
				],
				false
			],
			// two numbers
			[
				'4.3.1',
				[
					'require' => '4.4',
				],
				false
			],
			// one number fail
			[
				'4.3.1',
				[
					'require' => '5',
				],
				false
			],
			// pre-alpha app
			[
				'5.0.3',
				[
					'require' => '4.93',
				],
				true
			],
			// pre-alpha OC
			[
				'6.90.0.2',
				[
					'require' => '6.90',
				],
				true
			],
			// pre-alpha OC max
			[
				'6.90.0.2',
				[
					'requiremax' => '7',
				],
				true
			],
			// expect same major number match
			[
				'5.0.3',
				[
					'require' => '5',
				],
				true
			],
			// expect same major number match
			[
				'5.0.3',
				[
					'requiremax' => '5',
				],
				true
			],
			// dependencies versions before require*
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '7.0',
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '7.0',
								'max-version' => '7.0',
							],
						],
					],
				],
				false
			],
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '7.0',
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '5.0',
								'max-version' => '5.0',
							],
						],
					],
				],
				false
			],
			[
				'6.0.0.0',
				[
					'requiremin' => '5.0',
					'requiremax' => '5.0',
					'dependencies' => [
						'owncloud' => [
							'@attributes' => [
								'min-version' => '5.0',
								'max-version' => '7.0',
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
		];
	}

	/**
	 * @dataProvider appVersionsProvider
	 */
	public function testIsAppCompatible($ocVersion, $appInfo, $expectedResult): void {
		$this->assertEquals($expectedResult, \OC_App::isAppCompatible($ocVersion, $appInfo));
	}

	/**
	 * Tests that the app order is correct
	 */
	public function testGetEnabledAppsIsSorted(): void {
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
	public static function appConfigValuesProvider(): array {
		return [
			// logged in user1
			[
				self::TEST_USER1,
				[
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
					'profile',
					'provisioning_api',
					'settings',
					'theming',
					'twofactor_backupcodes',
					'viewer',
					'workflowengine',
				],
				false
			],
			// logged in user2
			[
				self::TEST_USER2,
				[
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
					'profile',
					'provisioning_api',
					'settings',
					'theming',
					'twofactor_backupcodes',
					'viewer',
					'workflowengine',
				],
				false
			],
			// logged in user3
			[
				self::TEST_USER3,
				[
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
					'profile',
					'provisioning_api',
					'settings',
					'theming',
					'twofactor_backupcodes',
					'viewer',
					'workflowengine',
				],
				false
			],
			//  no user, returns all apps
			[
				null,
				[
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
					'profile',
					'provisioning_api',
					'settings',
					'theming',
					'twofactor_backupcodes',
					'viewer',
					'workflowengine',
				],
				false,
			],
			//  user given, but ask for all
			[
				self::TEST_USER1,
				[
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
					'profile',
					'provisioning_api',
					'settings',
					'theming',
					'twofactor_backupcodes',
					'viewer',
					'workflowengine',
				],
				true,
			],
		];
	}

	/**
	 * Test enabled apps
	 *
	 * @dataProvider appConfigValuesProvider
	 */
	public function testEnabledApps($user, $expectedApps, $forceAll): void {
		$userManager = Server::get(IUserManager::class);
		$groupManager = Server::get(IGroupManager::class);
		$user1 = $userManager->createUser(self::TEST_USER1, 'NotAnEasyPassword123456+');
		$user2 = $userManager->createUser(self::TEST_USER2, 'NotAnEasyPassword123456_');
		$user3 = $userManager->createUser(self::TEST_USER3, 'NotAnEasyPassword123456?');

		$group1 = $groupManager->createGroup(self::TEST_GROUP1);
		$group1->addUser($user1);
		$group1->addUser($user3);
		$group2 = $groupManager->createGroup(self::TEST_GROUP2);
		$group2->addUser($user2);
		$group2->addUser($user3);

		\OC_User::setUserId($user);

		$this->setupAppConfigMock()->expects($this->once())
			->method('searchValues')
			->willReturn(
				[
					'app3' => 'yes',
					'app2' => 'no',
					'app1' => 'yes',
					'appforgroup1' => '["group1"]',
					'appforgroup2' => '["group2"]',
					'appforgroup12' => '["group2","group1"]',
				]
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
	public function testEnabledAppsCache(): void {
		$userManager = Server::get(IUserManager::class);
		$user1 = $userManager->createUser(self::TEST_USER1, 'NotAnEasyPassword123456+');

		\OC_User::setUserId(self::TEST_USER1);

		$this->setupAppConfigMock()->expects($this->once())
			->method('searchValues')
			->willReturn(
				[
					'app3' => 'yes',
					'app2' => 'no',
				]
			);

		$apps = \OC_App::getEnabledApps();
		$this->assertEquals(['files', 'app3', 'cloud_federation_api', 'dav', 'federatedfilesharing', 'lookup_server_connector', 'oauth2', 'profile', 'provisioning_api', 'settings', 'theming', 'twofactor_backupcodes', 'viewer', 'workflowengine'], $apps);

		// mock should not be called again here
		$apps = \OC_App::getEnabledApps();
		$this->assertEquals(['files', 'app3', 'cloud_federation_api', 'dav', 'federatedfilesharing', 'lookup_server_connector', 'oauth2', 'profile', 'provisioning_api', 'settings', 'theming', 'twofactor_backupcodes', 'viewer', 'workflowengine'], $apps);

		$this->restoreAppConfig();
		\OC_User::setUserId(null);

		$user1->delete();
	}


	private function setupAppConfigMock() {
		/** @var AppConfig|MockObject */
		$appConfig = $this->getMockBuilder(AppConfig::class)
			->onlyMethods(['searchValues'])
			->setConstructorArgs([Server::get(IDBConnection::class)])
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
		$this->overwriteService(AppConfig::class, $appConfig);
		$this->overwriteService(AppManager::class, new AppManager(
			Server::get(IUserSession::class),
			Server::get(IConfig::class),
			Server::get(IGroupManager::class),
			Server::get(ICacheFactory::class),
			Server::get(IEventDispatcher::class),
			Server::get(LoggerInterface::class),
			Server::get(ServerVersion::class),
		));
	}

	/**
	 * Restore the original app config service.
	 */
	private function restoreAppConfig() {
		$this->restoreService(AppConfig::class);
		$this->restoreService(AppManager::class);

		// Remove the cache of the mocked apps list with a forceRefresh
		\OC_App::getEnabledApps();
	}

	/**
	 * Providers for the app data values
	 */
	public static function appDataProvider(): array {
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
	public function testParseAppInfo(array $data, array $expected): void {
		$this->assertSame($expected, \OC_App::parseAppInfo($data));
	}

	public function testParseAppInfoL10N(): void {
		$parser = new InfoParser();
		$data = $parser->parse(\OC::$SERVERROOT . '/tests/data/app/description-multi-lang.xml');
		$this->assertEquals('English', \OC_App::parseAppInfo($data, 'en')['description']);
		$this->assertEquals('German', \OC_App::parseAppInfo($data, 'de')['description']);
	}

	public function testParseAppInfoL10NSingleLanguage(): void {
		$parser = new InfoParser();
		$data = $parser->parse(\OC::$SERVERROOT . '/tests/data/app/description-single-lang.xml');
		$this->assertEquals('English', \OC_App::parseAppInfo($data, 'en')['description']);
	}
}
