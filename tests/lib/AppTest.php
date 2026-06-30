<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\App\AppManager;
use OC\App\DependencyAnalyzer;
use OC\AppConfig;
use OC\Config\ConfigManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\ServerVersion;
use Psr\Log\LoggerInterface;

/**
 * Class AppTest
 */
class AppTest extends \Test\TestCase {
	public const TEST_USER1 = 'user1';
	public const TEST_USER2 = 'user2';
	public const TEST_USER3 = 'user3';
	public const TEST_GROUP1 = 'group1';
	public const TEST_GROUP2 = 'group2';

	private static IUser $user1;
	private static IUser $user2;
	private static IUser $user3;

	private static IGroup $group1;
	private static IGroup $group2;

	public static function setUpBeforeClass(): void {
		$userManager = Server::get(IUserManager::class);
		$groupManager = Server::get(IGroupManager::class);

		self::$user1 = $userManager->createUser(self::TEST_USER1, 'NotAnEasyPassword123456+');
		self::$user2 = $userManager->createUser(self::TEST_USER2, 'NotAnEasyPassword123456_');
		self::$user3 = $userManager->createUser(self::TEST_USER3, 'NotAnEasyPassword123456?');

		self::$group1 = $groupManager->createGroup(self::TEST_GROUP1);
		self::$group1->addUser(self::$user1);
		self::$group1->addUser(self::$user3);
		self::$group2 = $groupManager->createGroup(self::TEST_GROUP2);
		self::$group2->addUser(self::$user2);
		self::$group2->addUser(self::$user3);
	}

	public static function tearDownAfterClass(): void {
		self::$user1->delete();
		self::$user2->delete();
		self::$user3->delete();

		self::$group1->delete();
		self::$group2->delete();
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
					'appstore',
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
					'appstore',
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
					'appstore',
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
					'appstore',
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
					'appstore',
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
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('appConfigValuesProvider')]
	public function testEnabledApps($userId, $expectedApps, $forceAll): void {
		$userSession = Server::get(IUserSession::class);

		$user = match ($userId) {
			self::TEST_USER1 => self::$user1,
			self::TEST_USER2 => self::$user2,
			self::TEST_USER3 => self::$user3,
			default => null,
		};

		$userSession->setUser($user);

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
		$userSession->setUser(null);

		$this->assertEquals($expectedApps, $apps);
	}

	/**
	 * Test isEnabledApps() with cache, not re-reading the list of
	 * enabled apps more than once when a user is set.
	 */
	public function testEnabledAppsCache(): void {
		$userSession = Server::get(IUserSession::class);
		$userSession->setUser(self::$user1);

		$this->setupAppConfigMock()->expects($this->once())
			->method('searchValues')
			->willReturn(
				[
					'app3' => 'yes',
					'app2' => 'no',
				]
			);

		$apps = \OC_App::getEnabledApps();
		$this->assertEquals(['files', 'app3', 'appstore', 'cloud_federation_api', 'dav', 'federatedfilesharing', 'lookup_server_connector', 'oauth2', 'profile', 'provisioning_api', 'settings', 'theming', 'twofactor_backupcodes', 'viewer', 'workflowengine'], $apps);

		// mock should not be called again here
		$apps = \OC_App::getEnabledApps();
		$this->assertEquals(['files', 'app3', 'appstore', 'cloud_federation_api', 'dav', 'federatedfilesharing', 'lookup_server_connector', 'oauth2', 'profile', 'provisioning_api', 'settings', 'theming', 'twofactor_backupcodes', 'viewer', 'workflowengine'], $apps);

		$this->restoreAppConfig();
		$userSession->setUser(null);
	}

	private function setupAppConfigMock() {
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
			Server::get(ConfigManager::class),
			Server::get(DependencyAnalyzer::class),
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
}
