<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App;

use OC\App\AppManager;
use OC\AppConfig;
use OCP\App\AppPathNotFoundException;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class AppManagerTest
 *
 * @package Test\App
 */
class AppManagerTest extends TestCase {
	/**
	 * @return AppConfig|MockObject
	 */
	protected function getAppConfig() {
		$appConfig = [];
		$config = $this->createMock(AppConfig::class);

		$config->expects($this->any())
			->method('getValue')
			->willReturnCallback(function ($app, $key, $default) use (&$appConfig) {
				return (isset($appConfig[$app]) and isset($appConfig[$app][$key])) ? $appConfig[$app][$key] : $default;
			});
		$config->expects($this->any())
			->method('setValue')
			->willReturnCallback(function ($app, $key, $value) use (&$appConfig): void {
				if (!isset($appConfig[$app])) {
					$appConfig[$app] = [];
				}
				$appConfig[$app][$key] = $value;
			});
		$config->expects($this->any())
			->method('getValues')
			->willReturnCallback(function ($app, $key) use (&$appConfig) {
				if ($app) {
					return $appConfig[$app];
				} else {
					$values = [];
					foreach ($appConfig as $appid => $appData) {
						if (isset($appData[$key])) {
							$values[$appid] = $appData[$key];
						}
					}
					return $values;
				}
			});
		$config->expects($this->any())
			->method('searchValues')
			->willReturnCallback(function ($key, $lazy, $type) use (&$appConfig) {
				$values = [];
				foreach ($appConfig as $appid => $appData) {
					if (isset($appData[$key])) {
						$values[$appid] = $appData[$key];
					}
				}
				return $values;
			});

		return $config;
	}

	/** @var IUserSession|MockObject */
	protected $userSession;

	/** @var IConfig|MockObject */
	private $config;

	/** @var IGroupManager|MockObject */
	protected $groupManager;

	/** @var AppConfig|MockObject */
	protected $appConfig;

	/** @var ICache|MockObject */
	protected $cache;

	/** @var ICacheFactory|MockObject */
	protected $cacheFactory;

	/** @var IEventDispatcher|MockObject */
	protected $eventDispatcher;

	/** @var LoggerInterface|MockObject */
	protected $logger;

	protected IURLGenerator&MockObject $urlGenerator;

	protected ServerVersion&MockObject $serverVersion;

	/** @var IAppManager */
	protected $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->getAppConfig();
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);

		$this->overwriteService(AppConfig::class, $this->appConfig);
		$this->overwriteService(IURLGenerator::class, $this->urlGenerator);

		$this->cacheFactory->expects($this->any())
			->method('createDistributed')
			->with('settings')
			->willReturn($this->cache);

		$this->config
			->method('getSystemValueBool')
			->with('installed', false)
			->willReturn(true);

		$this->manager = new AppManager(
			$this->userSession,
			$this->config,
			$this->groupManager,
			$this->cacheFactory,
			$this->eventDispatcher,
			$this->logger,
			$this->serverVersion,
		);
	}

	/**
	 * @dataProvider dataGetAppIcon
	 */
	public function testGetAppIcon($callback, ?bool $dark, ?string $expected): void {
		$this->urlGenerator->expects($this->atLeastOnce())
			->method('imagePath')
			->willReturnCallback($callback);

		if ($dark !== null) {
			$this->assertEquals($expected, $this->manager->getAppIcon('test', $dark));
		} else {
			$this->assertEquals($expected, $this->manager->getAppIcon('test'));
		}
	}

	public static function dataGetAppIcon(): array {
		$nothing = function ($appId): void {
			self::assertEquals('test', $appId);
			throw new \RuntimeException();
		};

		$createCallback = function ($workingIcons) {
			return function ($appId, $icon) use ($workingIcons) {
				self::assertEquals('test', $appId);
				if (in_array($icon, $workingIcons)) {
					return '/path/' . $icon;
				}
				throw new \RuntimeException();
			};
		};

		return [
			'does not find anything' => [
				$nothing,
				false,
				null,
			],
			'nothing if request dark but only bright available' => [
				$createCallback(['app.svg']),
				true,
				null,
			],
			'nothing if request bright but only dark available' => [
				$createCallback(['app-dark.svg']),
				false,
				null,
			],
			'bright and only app.svg' => [
				$createCallback(['app.svg']),
				false,
				'/path/app.svg',
			],
			'dark and only app-dark.svg' => [
				$createCallback(['app-dark.svg']),
				true,
				'/path/app-dark.svg',
			],
			'dark only appname -dark.svg' => [
				$createCallback(['test-dark.svg']),
				true,
				'/path/test-dark.svg',
			],
			'bright and only appname.svg' => [
				$createCallback(['test.svg']),
				false,
				'/path/test.svg',
			],
			'priotize custom over default' => [
				$createCallback(['app.svg', 'test.svg']),
				false,
				'/path/test.svg',
			],
			'defaults to bright' => [
				$createCallback(['test-dark.svg', 'test.svg']),
				null,
				'/path/test.svg',
			],
			'no dark icon on default' => [
				$createCallback(['test-dark.svg', 'test.svg', 'app-dark.svg', 'app.svg']),
				false,
				'/path/test.svg',
			],
			'no bright icon on dark' => [
				$createCallback(['test-dark.svg', 'test.svg', 'app-dark.svg', 'app.svg']),
				true,
				'/path/test-dark.svg',
			],
		];
	}

	public function testEnableApp(): void {
		// making sure "files_trashbin" is disabled
		if ($this->manager->isEnabledForUser('files_trashbin')) {
			$this->manager->disableApp('files_trashbin');
		}
		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new AppEnableEvent('files_trashbin'));
		$this->manager->enableApp('files_trashbin');
		$this->assertEquals('yes', $this->appConfig->getValue('files_trashbin', 'enabled', 'no'));
	}

	public function testDisableApp(): void {
		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new AppDisableEvent('files_trashbin'));
		$this->manager->disableApp('files_trashbin');
		$this->assertEquals('no', $this->appConfig->getValue('files_trashbin', 'enabled', 'no'));
	}

	public function testNotEnableIfNotInstalled(): void {
		try {
			$this->manager->enableApp('some_random_name_which_i_hope_is_not_an_app');
			$this->assertFalse(true, 'If this line is reached the expected exception is not thrown.');
		} catch (AppPathNotFoundException $e) {
			// Exception is expected
			$this->assertEquals('Could not find path for some_random_name_which_i_hope_is_not_an_app', $e->getMessage());
		}

		$this->assertEquals('no', $this->appConfig->getValue(
			'some_random_name_which_i_hope_is_not_an_app', 'enabled', 'no'
		));
	}

	public function testEnableAppForGroups(): void {
		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')
			->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')
			->willReturn('group2');

		$groups = [$group1, $group2];

		/** @var AppManager|MockObject $manager */
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods([
				'getAppPath',
			])
			->getMock();

		$manager->expects($this->exactly(2))
			->method('getAppPath')
			->with('test')
			->willReturn('apps/test');

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new AppEnableEvent('test', ['group1', 'group2']));

		$manager->enableAppForGroups('test', $groups);
		$this->assertEquals('["group1","group2"]', $this->appConfig->getValue('test', 'enabled', 'no'));
	}

	public static function dataEnableAppForGroupsAllowedTypes(): array {
		return [
			[[]],
			[[
				'types' => [],
			]],
			[[
				'types' => ['nickvergessen'],
			]],
		];
	}

	/**
	 * @dataProvider dataEnableAppForGroupsAllowedTypes
	 *
	 * @param array $appInfo
	 */
	public function testEnableAppForGroupsAllowedTypes(array $appInfo): void {
		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')
			->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')
			->willReturn('group2');

		$groups = [$group1, $group2];

		/** @var AppManager|MockObject $manager */
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods([
				'getAppPath',
				'getAppInfo',
			])
			->getMock();

		$manager->expects($this->once())
			->method('getAppPath')
			->with('test')
			->willReturn('');

		$manager->expects($this->once())
			->method('getAppInfo')
			->with('test')
			->willReturn($appInfo);

		$this->eventDispatcher->expects($this->once())->method('dispatchTyped')->with(new AppEnableEvent('test', ['group1', 'group2']));

		$manager->enableAppForGroups('test', $groups);
		$this->assertEquals('["group1","group2"]', $this->appConfig->getValue('test', 'enabled', 'no'));
	}

	public static function dataEnableAppForGroupsForbiddenTypes(): array {
		return [
			['filesystem'],
			['prelogin'],
			['authentication'],
			['logging'],
			['prevent_group_restriction'],
		];
	}

	/**
	 * @dataProvider dataEnableAppForGroupsForbiddenTypes
	 *
	 * @param string $type
	 *
	 */
	public function testEnableAppForGroupsForbiddenTypes($type): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('test can\'t be enabled for groups.');

		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')
			->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')
			->willReturn('group2');

		$groups = [$group1, $group2];

		/** @var AppManager|MockObject $manager */
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods([
				'getAppPath',
				'getAppInfo',
			])
			->getMock();

		$manager->expects($this->once())
			->method('getAppPath')
			->with('test')
			->willReturn('');

		$manager->expects($this->once())
			->method('getAppInfo')
			->with('test')
			->willReturn([
				'types' => [$type],
			]);

		$this->eventDispatcher->expects($this->never())->method('dispatchTyped')->with(new AppEnableEvent('test', ['group1', 'group2']));

		$manager->enableAppForGroups('test', $groups);
	}

	public function testIsInstalledEnabled(): void {
		$this->appConfig->setValue('test', 'enabled', 'yes');
		$this->assertTrue($this->manager->isEnabledForAnyone('test'));
	}

	public function testIsInstalledDisabled(): void {
		$this->appConfig->setValue('test', 'enabled', 'no');
		$this->assertFalse($this->manager->isEnabledForAnyone('test'));
	}

	public function testIsInstalledEnabledForGroups(): void {
		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertTrue($this->manager->isEnabledForAnyone('test'));
	}

	private function newUser($uid) {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($uid);

		return $user;
	}

	public function testIsEnabledForUserEnabled(): void {
		$this->appConfig->setValue('test', 'enabled', 'yes');
		$user = $this->newUser('user1');
		$this->assertTrue($this->manager->isEnabledForUser('test', $user));
	}

	public function testIsEnabledForUserDisabled(): void {
		$this->appConfig->setValue('test', 'enabled', 'no');
		$user = $this->newUser('user1');
		$this->assertFalse($this->manager->isEnabledForUser('test', $user));
	}

	public function testGetAppPath(): void {
		$this->assertEquals(\OC::$SERVERROOT . '/apps/files', $this->manager->getAppPath('files'));
	}

	public function testGetAppPathSymlink(): void {
		$fakeAppDirname = sha1(uniqid('test', true));
		$fakeAppPath = sys_get_temp_dir() . '/' . $fakeAppDirname;
		$fakeAppLink = \OC::$SERVERROOT . '/' . $fakeAppDirname;

		mkdir($fakeAppPath);
		if (symlink($fakeAppPath, $fakeAppLink) === false) {
			$this->markTestSkipped('Failed to create symlink');
		}

		// Use the symlink as the app path
		\OC::$APPSROOTS[] = [
			'path' => $fakeAppLink,
			'url' => \OC::$WEBROOT . '/' . $fakeAppDirname,
			'writable' => false,
		];

		$fakeTestAppPath = $fakeAppPath . '/' . 'test-test-app';
		mkdir($fakeTestAppPath);

		$generatedAppPath = $this->manager->getAppPath('test-test-app');

		rmdir($fakeTestAppPath);
		unlink($fakeAppLink);
		rmdir($fakeAppPath);

		$this->assertEquals($fakeAppLink . '/test-test-app', $generatedAppPath);
	}

	public function testGetAppPathFail(): void {
		$this->expectException(AppPathNotFoundException::class);
		$this->manager->getAppPath('testnotexisting');
	}

	public function testIsEnabledForUserEnabledForGroup(): void {
		$user = $this->newUser('user1');
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['foo', 'bar']);

		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertTrue($this->manager->isEnabledForUser('test', $user));
	}

	public function testIsEnabledForUserDisabledForGroup(): void {
		$user = $this->newUser('user1');
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['bar']);

		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertFalse($this->manager->isEnabledForUser('test', $user));
	}

	public function testIsEnabledForUserLoggedOut(): void {
		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertFalse($this->manager->isEnabledForUser('test'));
	}

	public function testIsEnabledForUserLoggedIn(): void {
		$user = $this->newUser('user1');

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['foo', 'bar']);

		$this->appConfig->setValue('test', 'enabled', '["foo"]');
		$this->assertTrue($this->manager->isEnabledForUser('test'));
	}

	public function testGetEnabledApps(): void {
		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'enabled', 'no');
		$this->appConfig->setValue('test3', 'enabled', '["foo"]');
		$apps = [
			'cloud_federation_api',
			'dav',
			'federatedfilesharing',
			'files',
			'lookup_server_connector',
			'oauth2',
			'profile',
			'provisioning_api',
			'settings',
			'test1',
			'test3',
			'theming',
			'twofactor_backupcodes',
			'viewer',
			'workflowengine',
		];
		$this->assertEquals($apps, $this->manager->getEnabledApps());
	}

	public function testGetAppsForUser(): void {
		$user = $this->newUser('user1');
		$this->groupManager->expects($this->any())
			->method('getUserGroupIds')
			->with($user)
			->willReturn(['foo', 'bar']);

		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'enabled', 'no');
		$this->appConfig->setValue('test3', 'enabled', '["foo"]');
		$this->appConfig->setValue('test4', 'enabled', '["asd"]');
		$enabled = [
			'cloud_federation_api',
			'dav',
			'federatedfilesharing',
			'files',
			'lookup_server_connector',
			'oauth2',
			'profile',
			'provisioning_api',
			'settings',
			'test1',
			'test3',
			'theming',
			'twofactor_backupcodes',
			'viewer',
			'workflowengine',
		];
		$this->assertEquals($enabled, $this->manager->getEnabledAppsForUser($user));
	}

	public function testGetAppsNeedingUpgrade(): void {
		/** @var AppManager|MockObject $manager */
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods(['getAppInfo'])
			->getMock();

		$appInfos = [
			'cloud_federation_api' => ['id' => 'cloud_federation_api'],
			'dav' => ['id' => 'dav'],
			'files' => ['id' => 'files'],
			'federatedfilesharing' => ['id' => 'federatedfilesharing'],
			'profile' => ['id' => 'profile'],
			'provisioning_api' => ['id' => 'provisioning_api'],
			'lookup_server_connector' => ['id' => 'lookup_server_connector'],
			'test1' => ['id' => 'test1', 'version' => '1.0.1', 'requiremax' => '9.0.0'],
			'test2' => ['id' => 'test2', 'version' => '1.0.0', 'requiremin' => '8.2.0'],
			'test3' => ['id' => 'test3', 'version' => '1.2.4', 'requiremin' => '9.0.0'],
			'test4' => ['id' => 'test4', 'version' => '3.0.0', 'requiremin' => '8.1.0'],
			'testnoversion' => ['id' => 'testnoversion', 'requiremin' => '8.2.0'],
			'settings' => ['id' => 'settings'],
			'theming' => ['id' => 'theming'],
			'twofactor_backupcodes' => ['id' => 'twofactor_backupcodes'],
			'viewer' => ['id' => 'viewer'],
			'workflowengine' => ['id' => 'workflowengine'],
			'oauth2' => ['id' => 'oauth2'],
		];

		$manager->expects($this->any())
			->method('getAppInfo')
			->willReturnCallback(
				function ($appId) use ($appInfos) {
					return $appInfos[$appId];
				}
			);

		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test1', 'installed_version', '1.0.0');
		$this->appConfig->setValue('test2', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'installed_version', '1.0.0');
		$this->appConfig->setValue('test3', 'enabled', 'yes');
		$this->appConfig->setValue('test3', 'installed_version', '1.0.0');
		$this->appConfig->setValue('test4', 'enabled', 'yes');
		$this->appConfig->setValue('test4', 'installed_version', '2.4.0');

		$apps = $manager->getAppsNeedingUpgrade('8.2.0');

		$this->assertCount(2, $apps);
		$this->assertEquals('test1', $apps[0]['id']);
		$this->assertEquals('test4', $apps[1]['id']);
	}

	public function testGetIncompatibleApps(): void {
		/** @var AppManager|MockObject $manager */
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods(['getAppInfo'])
			->getMock();

		$appInfos = [
			'cloud_federation_api' => ['id' => 'cloud_federation_api'],
			'dav' => ['id' => 'dav'],
			'files' => ['id' => 'files'],
			'federatedfilesharing' => ['id' => 'federatedfilesharing'],
			'profile' => ['id' => 'profile'],
			'provisioning_api' => ['id' => 'provisioning_api'],
			'lookup_server_connector' => ['id' => 'lookup_server_connector'],
			'test1' => ['id' => 'test1', 'version' => '1.0.1', 'requiremax' => '8.0.0'],
			'test2' => ['id' => 'test2', 'version' => '1.0.0', 'requiremin' => '8.2.0'],
			'test3' => ['id' => 'test3', 'version' => '1.2.4', 'requiremin' => '9.0.0'],
			'settings' => ['id' => 'settings'],
			'testnoversion' => ['id' => 'testnoversion', 'requiremin' => '8.2.0'],
			'theming' => ['id' => 'theming'],
			'twofactor_backupcodes' => ['id' => 'twofactor_backupcodes'],
			'workflowengine' => ['id' => 'workflowengine'],
			'oauth2' => ['id' => 'oauth2'],
			'viewer' => ['id' => 'viewer'],
		];

		$manager->expects($this->any())
			->method('getAppInfo')
			->willReturnCallback(
				function ($appId) use ($appInfos) {
					return $appInfos[$appId];
				}
			);

		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'enabled', 'yes');
		$this->appConfig->setValue('test3', 'enabled', 'yes');

		$apps = $manager->getIncompatibleApps('8.2.0');

		$this->assertCount(2, $apps);
		$this->assertEquals('test1', $apps[0]['id']);
		$this->assertEquals('test3', $apps[1]['id']);
	}

	public function testGetEnabledAppsForGroup(): void {
		$group = $this->createMock(IGroup::class);
		$group->expects($this->any())
			->method('getGID')
			->willReturn('foo');

		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'enabled', 'no');
		$this->appConfig->setValue('test3', 'enabled', '["foo"]');
		$this->appConfig->setValue('test4', 'enabled', '["asd"]');
		$enabled = [
			'cloud_federation_api',
			'dav',
			'federatedfilesharing',
			'files',
			'lookup_server_connector',
			'oauth2',
			'profile',
			'provisioning_api',
			'settings',
			'test1',
			'test3',
			'theming',
			'twofactor_backupcodes',
			'viewer',
			'workflowengine',
		];
		$this->assertEquals($enabled, $this->manager->getEnabledAppsForGroup($group));
	}

	public function testGetAppRestriction(): void {
		$this->appConfig->setValue('test1', 'enabled', 'yes');
		$this->appConfig->setValue('test2', 'enabled', 'no');
		$this->appConfig->setValue('test3', 'enabled', '["foo"]');

		$this->assertEquals([], $this->manager->getAppRestriction('test1'));
		$this->assertEquals([], $this->manager->getAppRestriction('test2'));
		$this->assertEquals(['foo'], $this->manager->getAppRestriction('test3'));
	}

	public static function isBackendRequiredDataProvider(): array {
		return [
			// backend available
			[
				'caldav',
				['app1' => ['caldav']],
				true,
			],
			[
				'caldav',
				['app1' => [], 'app2' => ['foo'], 'app3' => ['caldav']],
				true,
			],
			// backend not available
			[
				'caldav',
				['app3' => [], 'app1' => ['foo'], 'app2' => ['bar', 'baz']],
				false,
			],
			// no app available
			[
				'caldav',
				[],
				false,
			],
		];
	}

	/**
	 * @dataProvider isBackendRequiredDataProvider
	 */
	public function testIsBackendRequired(
		string $backend,
		array $appBackends,
		bool $expected,
	): void {
		$appInfoData = array_map(
			static fn (array $backends) => ['dependencies' => ['backend' => $backends]],
			$appBackends,
		);

		$reflection = new \ReflectionClass($this->manager);
		$property = $reflection->getProperty('appInfos');
		$property->setValue($this->manager, $appInfoData);

		$this->assertEquals($expected, $this->manager->isBackendRequired($backend));
	}

	public function testGetAppVersion() {
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods([
				'getAppInfo',
			])
			->getMock();

		$manager->expects(self::once())
			->method('getAppInfo')
			->with('myapp')
			->willReturn(['version' => '99.99.99-rc.99']);

		$this->serverVersion
			->expects(self::never())
			->method('getVersionString');

		$this->assertEquals(
			'99.99.99-rc.99',
			$manager->getAppVersion('myapp'),
		);
	}

	public function testGetAppVersionCore() {
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods([
				'getAppInfo',
			])
			->getMock();

		$manager->expects(self::never())
			->method('getAppInfo');

		$this->serverVersion
			->expects(self::once())
			->method('getVersionString')
			->willReturn('1.2.3-beta.4');

		$this->assertEquals(
			'1.2.3-beta.4',
			$manager->getAppVersion('core'),
		);
	}

	public function testGetAppVersionUnknown() {
		$manager = $this->getMockBuilder(AppManager::class)
			->setConstructorArgs([
				$this->userSession,
				$this->config,
				$this->groupManager,
				$this->cacheFactory,
				$this->eventDispatcher,
				$this->logger,
				$this->serverVersion,
			])
			->onlyMethods([
				'getAppInfo',
			])
			->getMock();

		$manager->expects(self::once())
			->method('getAppInfo')
			->with('unknown')
			->willReturn(null);

		$this->serverVersion
			->expects(self::never())
			->method('getVersionString');

		$this->assertEquals(
			'0',
			$manager->getAppVersion('unknown'),
		);
	}

}
