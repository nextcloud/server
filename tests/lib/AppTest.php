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
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class AppTest
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class AppTest extends \Test\TestCase {
	public const TEST_USER1 = 'user1';
	public const TEST_USER2 = 'user2';
	public const TEST_USER3 = 'user3';
	public const TEST_GROUP1 = 'group1';
	public const TEST_GROUP2 = 'group2';

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
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('appConfigValuesProvider')]
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
