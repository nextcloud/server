<?php

/**
 * SPDX-FileCopyrightText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\Tests\Service;

use OCA\Files\AppInfo\Application;
use OCA\Files\Service\UserConfig;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Class UserConfigTest
 *
 * @group DB
 *
 * @package OCA\Files
 */
class UserConfigTest extends \Test\TestCase {
	/**
	 * @var string
	 */
	private $userUID;

	private $userMock;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $configMock;

	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSessionMock;

	/** @var IAppConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $appConfigMock;

	/**
	 * @var UserConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $userConfigService;

	protected function setUp(): void {
		parent::setUp();
		$this->configMock = $this->createMock(IConfig::class);
		$this->appConfigMock = $this->createMock(IAppConfig::class);

		$this->userUID = static::getUniqueID('user_id-');
		\OC::$server->getUserManager()->createUser($this->userUID, 'test');
		\OC_User::setUserId($this->userUID);
		\OC_Util::setupFS($this->userUID);
		$this->userMock = $this->getUserMock($this->userUID);

		$this->userSessionMock = $this->createMock(IUserSession::class);
		$this->userSessionMock->expects($this->any())
			->method('getUser')
			->withAnyParameters()
			->willReturn($this->userMock);

		$this->userConfigService = $this->getUserConfigService(['addActivity']);
	}

	/**
	 * @param array $methods
	 * @return UserConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getUserConfigService(array $methods = []) {
		return $this->getMockBuilder(UserConfig::class)
			->setConstructorArgs([
				$this->configMock,
				$this->userSessionMock,
				$this->appConfigMock,
			])
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @param string $uid
	 * @return IUser|MockObject
	 */
	protected function getUserMock($uid) {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);
		return $user;
	}
	protected function tearDown(): void {
		\OC_User::setUserId('');
		$user = \OC::$server->getUserManager()->get($this->userUID);
		if ($user !== null) {
			$user->delete();
		}
	}
	public function testThrowsExceptionWhenNoUserLoggedInForSetConfig(): void {
		$this->userSessionMock = $this->createMock(IUserSession::class);
		$this->userSessionMock->expects($this->any())
			->method('getUser')
			->withAnyParameters()
			->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('No user logged in');

		$userConfig = new UserConfig($this->configMock, $this->userSessionMock, $this->appConfigMock);
		$userConfig->setConfig('crop_image_previews', true);
	}

	public function testThrowsInvalidArgumentExceptionForUnknownConfigKey(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown config key');

		$userConfig = new UserConfig($this->configMock, $this->userSessionMock, $this->appConfigMock);
		$userConfig->setConfig('unknown_key', true);
	}

	public function testThrowsInvalidArgumentExceptionForInvalidConfigValue(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid config value');

		$userConfig = new UserConfig($this->configMock, $this->userSessionMock, $this->appConfigMock);
		$userConfig->setConfig('crop_image_previews', 'foo');
	}

	public static function validBoolConfigValues(): array {
		return [
			['true', '1'],
			['false', '0'],
			['1', '1'],
			['0', '0'],
			['yes', '1'],
			['no', '0'],
			[true, '1'],
			[false, '0'],
		];
	}

	/**
	 * @dataProvider validBoolConfigValues
	 */
	public function testSetsConfigWithBooleanValuesSuccessfully($boolValue, $expectedValue): void {
		$this->configMock->expects($this->once())
			->method('setUserValue')
			->with($this->userUID, Application::APP_ID, 'crop_image_previews', $expectedValue);

		$userConfig = new UserConfig($this->configMock, $this->userSessionMock, $this->appConfigMock);
		$userConfig->setConfig('crop_image_previews', $boolValue);
	}

	public function testGetsConfigsWithDefaultValuesSuccessfully(): void {
		$this->userSessionMock->method('getUser')->willReturn($this->userMock);
		$this->configMock->method('getUserValue')
			->willReturnCallback(function ($userId, $appId, $key, $default) {
				return $default;
			});

		// pass the default app settings unchanged
		$this->appConfigMock->method('getAppValueBool')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$userConfig = new UserConfig($this->configMock, $this->userSessionMock, $this->appConfigMock);
		$configs = $userConfig->getConfigs();
		$this->assertEquals([
			'crop_image_previews' => true,
			'show_hidden' => false,
			'sort_favorites_first' => true,
			'sort_folders_first' => true,
			'grid_view' => false,
			'folder_tree' => true,
		], $configs);
	}

	private function getDefaultConfigValue(string $key) {
		foreach (UserConfig::ALLOWED_CONFIGS as $config) {
			if ($config['key'] === $key) {
				return $config['default'];
			}
		}
		return '';
	}

	public function testGetsConfigsOverrideWithUserValuesSuccessfully(): void {
		$this->userSessionMock->method('getUser')->willReturn($this->userMock);
		$this->configMock->method('getUserValue')
			->willReturnCallback(function ($userId, $appId, $key, $default) {

				// Override the default values
				if ($key === 'crop_image_previews') {
					return !$this->getDefaultConfigValue($key);
				} elseif ($key === 'show_hidden') {
					return !$this->getDefaultConfigValue($key);
				}

				return $default;
			});

		// pass the default app settings unchanged
		$this->appConfigMock->method('getAppValueBool')
			->willReturnCallback(function ($key, $default) {
				return $default;
			});

		$userConfig = new UserConfig($this->configMock, $this->userSessionMock, $this->appConfigMock);
		$configs = $userConfig->getConfigs();
		$this->assertEquals([
			'crop_image_previews' => false,
			'show_hidden' => true,
			'sort_favorites_first' => true,
			'sort_folders_first' => true,
			'grid_view' => false,
			'folder_tree' => true,
		], $configs);
	}

	public static function overrideConfigValues(): array {
		// app config key, default value, user value, admin value, expected value, test description
		return [
			// default value is set due to no user and no admin values
			['show_hidden', false, null, null, false, "user has no setting, admin has no setting - user gets default setting"],

			// admin value is set due to no user value
			['show_hidden', false, null, true, true, "user has no setting, admin value is set - user gets admin setting"],
			['show_hidden', false, null, false, false, "user has no setting, admin value is set - user gets admin setting"],

			// admin value is ignored in favor of user value
			['show_hidden', false, true, null, true, "user overrides default setting, admin has no setting - user gets user setting"],
			['show_hidden', false, true, true, true, "user overrides default setting, admin value is set - user gets user setting"],
			['show_hidden', false, true, false, true, "user overrides default setting, admin value is set - user gets user setting"],

			['show_hidden', false, false, null, false, "user sets default setting, admin has no setting - user gets user setting"],
			['show_hidden', false, false, true, false, "user sets default setting, admin value is set - user gets user setting"],
			['show_hidden', false, false, false, false, "user sets default setting, admin value is set - user gets user setting"],
		];
	}

	/**
	 * @dataProvider overrideConfigValues
	 */
	public function testGetsConfigsOverrideWithAppsValuesSuccessfully(string $appConfigKey, bool $defaultValue, $userValue, $adminValue, $expectedValue, $testDescription): void {
		$this->userSessionMock->method('getUser')->willReturn($this->userMock);

		// emulate override by the user config values
		$this->configMock->method('getUserValue')
			->willReturnCallback(function ($userId, $appId, $key, $default) use ($appConfigKey, $userValue) {
				if ($key === $appConfigKey) {
					return $userValue === null ? $default : $userValue;
				}
				return $default;
			});

		// emulate override by the app config values
		$this->appConfigMock->method('getAppValueBool')
			->willReturnCallback(function ($key, $default) use ($adminValue, $appConfigKey) {
				if ($key === $appConfigKey) {
					return $adminValue === null ? $default : $adminValue;
				}
				return $default;
			});

		$userConfig = new UserConfig($this->configMock, $this->userSessionMock, $this->appConfigMock);
		$configs = $userConfig->getConfigs();

		// ony the expected value should be different from the default
		$expectedConfigs = $configs;
		$expectedConfigs[$appConfigKey] = $expectedValue;

		$this->assertEquals($expectedConfigs, $configs, $testDescription);
	}
}
