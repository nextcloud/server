<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Tests\Controller;

use OC\AppConfig;
use OC\Config\ConfigManager;
use OCA\Provisioning_API\Controller\AppConfigController;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use OCP\Settings\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;
use function json_decode;
use function json_encode;

/**
 * Class AppConfigControllerTest
 *
 * @package OCA\Provisioning_API\Tests
 */
class AppConfigControllerTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IUserSession&MockObject $userSession;
	private IL10N&MockObject $l10n;
	private IManager&MockObject $settingManager;
	private IGroupManager&MockObject $groupManager;
	private IAppManager $appManager;
	private ConfigManager $configManager;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(AppConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->settingManager = $this->createMock(IManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->appManager = Server::get(IAppManager::class);
		$this->configManager = Server::get(ConfigManager::class);
	}

	/**
	 * @param string[] $methods
	 * @return AppConfigController|MockObject
	 */
	protected function getInstance(array $methods = []) {
		$request = $this->createMock(IRequest::class);

		if (empty($methods)) {
			return new AppConfigController(
				'provisioning_api',
				$request,
				$this->appConfig,
				$this->userSession,
				$this->l10n,
				$this->groupManager,
				$this->settingManager,
				$this->appManager,
				$this->configManager,
			);
		} else {
			return $this->getMockBuilder(AppConfigController::class)
				->setConstructorArgs([
					'provisioning_api',
					$request,
					$this->appConfig,
					$this->userSession,
					$this->l10n,
					$this->groupManager,
					$this->settingManager,
					$this->appManager,
					$this->configManager,
				])
				->onlyMethods($methods)
				->getMock();
		}
	}

	public function testGetApps(): void {
		$this->appConfig->expects($this->once())
			->method('getApps')
			->willReturn(['apps']);

		$result = $this->getInstance()->getApps();
		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertSame(Http::STATUS_OK, $result->getStatus());
		$this->assertEquals(['data' => ['apps']], $result->getData());
	}

	public static function dataGetKeys(): array {
		return [
			['app1 ', null, new \InvalidArgumentException('error'), Http::STATUS_FORBIDDEN],
			['app2', ['keys'], null, Http::STATUS_OK],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetKeys')]
	public function testGetKeys(string $app, ?array $keys, ?\Throwable $throws, int $status): void {
		$api = $this->getInstance(['verifyAppId']);
		if ($throws instanceof \Exception) {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app)
				->willThrowException($throws);

			$this->appConfig->expects($this->never())
				->method('getKeys');
		} else {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app);

			$this->appConfig->expects($this->once())
				->method('getKeys')
				->with($app)
				->willReturn($keys);
		}

		$result = $api->getKeys($app);
		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertSame($status, $result->getStatus());
		if ($throws instanceof \Exception) {
			$this->assertEquals(['data' => ['message' => $throws->getMessage()]], $result->getData());
		} else {
			$this->assertEquals(['data' => $keys], $result->getData());
		}
	}

	public static function dataGetValue(): array {
		return [
			['app1', 'key', 'default', null, new \InvalidArgumentException('error'), Http::STATUS_FORBIDDEN],
			['app2', 'key', 'default', 'return', null, Http::STATUS_OK],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetValue')]
	public function testGetValue(string $app, string $key, string $default, ?string $return, ?\Throwable $throws, int $status): void {
		$api = $this->getInstance(['verifyAppId']);
		if ($throws instanceof \Exception) {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app)
				->willThrowException($throws);
		} else {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app);

			$this->appConfig->expects($this->once())
				->method('getValueMixed')
				->with($app, $key, $default)
				->willReturn($return);
		}

		$result = $api->getValue($app, $key, $default);
		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertSame($status, $result->getStatus());
		if ($throws instanceof \Exception) {
			$this->assertEquals(['data' => ['message' => $throws->getMessage()]], $result->getData());
		} else {
			$this->assertEquals(['data' => $return], $result->getData());
		}
	}

	public static function dataSetValue(): array {
		return [
			['app1', 'key', 'default', new \InvalidArgumentException('error1'), null, Http::STATUS_FORBIDDEN],
			['app2', 'key', 'default', null, new \InvalidArgumentException('error2'), Http::STATUS_FORBIDDEN],
			['app2', 'key', 'default', null, null, Http::STATUS_OK],
			['app2', 'key', '1', null, null, Http::STATUS_OK, IAppConfig::VALUE_BOOL],
			['app2', 'key', '42', null, null, Http::STATUS_OK, IAppConfig::VALUE_INT],
			['app2', 'key', '4.2', null, null, Http::STATUS_OK, IAppConfig::VALUE_FLOAT],
			['app2', 'key', '42', null, null, Http::STATUS_OK, IAppConfig::VALUE_STRING],
			['app2', 'key', 'secret', null, null, Http::STATUS_OK, IAppConfig::VALUE_STRING | IAppConfig::VALUE_SENSITIVE],
			['app2', 'key', json_encode([4, 2]), null, null, Http::STATUS_OK, IAppConfig::VALUE_ARRAY],
			['app2', 'key', json_encode([4, 2]), null, null, Http::STATUS_OK, new AppConfigUnknownKeyException()],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataSetValue')]
	public function testSetValue(string $app, string $key, string $value, ?\Throwable $appThrows, ?\Throwable $keyThrows, int $status, int|\Throwable $type = IAppConfig::VALUE_MIXED): void {
		$adminUser = $this->createMock(IUser::class);
		$adminUser->expects($this->once())
			->method('getUid')
			->willReturn('admin');

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($adminUser);
		$this->groupManager->expects($this->once())
			->method('isAdmin')
			->with('admin')
			->willReturn(true);
		$api = $this->getInstance(['verifyAppId', 'verifyConfigKey']);
		if ($appThrows instanceof \Exception) {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app)
				->willThrowException($appThrows);

			$api->expects($this->never())
				->method('verifyConfigKey');
			$this->appConfig->expects($this->never())
				->method('setValueMixed');
		} elseif ($keyThrows instanceof  \Exception) {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app);
			$api->expects($this->once())
				->method('verifyConfigKey')
				->with($app, $key)
				->willThrowException($keyThrows);

			$this->appConfig->expects($this->never())
				->method('setValueMixed');
		} else {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app);
			$api->expects($this->once())
				->method('verifyConfigKey')
				->with($app, $key);

			if ($type instanceof \Throwable) {
				$this->appConfig->expects($this->once())
					->method('getDetails')
					->with($app, $key)
					->willThrowException($type);
			} else {
				$this->appConfig->expects($this->once())
					->method('getDetails')
					->with($app, $key)
					->willReturn([
						'app' => $app,
						'key' => $key,
						'value' => '', // 🤷
						'type' => $type,
						'lazy' => false,
						'typeString' => (string)$type, // this is not accurate, but acceptable
						'sensitive' => ($type & IAppConfig::VALUE_SENSITIVE) !== 0,
					]);
			}

			$configValueSetter = match ($type) {
				IAppConfig::VALUE_BOOL => 'setValueBool',
				IAppConfig::VALUE_FLOAT => 'setValueFloat',
				IAppConfig::VALUE_INT => 'setValueInt',
				IAppConfig::VALUE_STRING => 'setValueString',
				IAppConfig::VALUE_ARRAY => 'setValueArray',
				default => 'setValueMixed',
			};

			$this->appConfig->expects($this->once())
				->method($configValueSetter)
				->with($app, $key, $configValueSetter === 'setValueArray' ? json_decode($value, true) : $value);
		}

		$result = $api->setValue($app, $key, $value);
		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertSame($status, $result->getStatus());
		if ($appThrows instanceof \Exception) {
			$this->assertEquals(['data' => ['message' => $appThrows->getMessage()]], $result->getData());
		} elseif ($keyThrows instanceof \Exception) {
			$this->assertEquals(['data' => ['message' => $keyThrows->getMessage()]], $result->getData());
		} else {
			$this->assertEquals([], $result->getData());
		}
	}

	public static function dataDeleteValue(): array {
		return [
			['app1', 'key', new \InvalidArgumentException('error1'), null, Http::STATUS_FORBIDDEN],
			['app2', 'key', null, new \InvalidArgumentException('error2'), Http::STATUS_FORBIDDEN],
			['app2', 'key', null, null, Http::STATUS_OK],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataDeleteValue')]
	public function testDeleteValue(string $app, string $key, ?\Throwable $appThrows, ?\Throwable $keyThrows, int $status): void {
		$api = $this->getInstance(['verifyAppId', 'verifyConfigKey']);
		if ($appThrows instanceof \Exception) {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app)
				->willThrowException($appThrows);

			$api->expects($this->never())
				->method('verifyConfigKey');
			$this->appConfig->expects($this->never())
				->method('deleteKey');
		} elseif ($keyThrows instanceof  \Exception) {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app);
			$api->expects($this->once())
				->method('verifyConfigKey')
				->with($app, $key)
				->willThrowException($keyThrows);

			$this->appConfig->expects($this->never())
				->method('deleteKey');
		} else {
			$api->expects($this->once())
				->method('verifyAppId')
				->with($app);
			$api->expects($this->once())
				->method('verifyConfigKey')
				->with($app, $key);

			$this->appConfig->expects($this->once())
				->method('deleteKey')
				->with($app, $key);
		}

		$result = $api->deleteKey($app, $key);
		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertSame($status, $result->getStatus());
		if ($appThrows instanceof \Exception) {
			$this->assertEquals(['data' => ['message' => $appThrows->getMessage()]], $result->getData());
		} elseif ($keyThrows instanceof \Exception) {
			$this->assertEquals(['data' => ['message' => $keyThrows->getMessage()]], $result->getData());
		} else {
			$this->assertEquals([], $result->getData());
		}
	}

	public function testVerifyAppId(): void {
		$api = $this->getInstance();
		$this->invokePrivate($api, 'verifyAppId', ['activity']);
		$this->addToAssertionCount(1);
	}

	public static function dataVerifyAppIdThrows(): array {
		return [
			['activity..'],
			['activity/'],
			['activity\\'],
			['activity\0'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataVerifyAppIdThrows')]
	public function testVerifyAppIdThrows(string $app): void {
		$this->expectException(\InvalidArgumentException::class);

		$api = $this->getInstance();
		$this->invokePrivate($api, 'verifyAppId', [$app]);
	}

	public static function dataVerifyConfigKey(): array {
		return [
			['activity', 'abc', ''],
			['dav', 'public_route', ''],
			['files', 'remote_route', ''],
			['core', 'encryption_enabled', 'yes'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataVerifyConfigKey')]
	public function testVerifyConfigKey(string $app, string $key, string $value): void {
		$api = $this->getInstance();
		$this->invokePrivate($api, 'verifyConfigKey', [$app, $key, $value]);
		$this->addToAssertionCount(1);
	}

	public static function dataVerifyConfigKeyThrows(): array {
		return [
			['activity', 'installed_version', ''],
			['calendar', 'enabled', ''],
			['contacts', 'types', ''],
			['core', 'encryption_enabled', 'no'],
			['core', 'encryption_enabled', ''],
			['core', 'public_files', ''],
			['core', 'public_dav', ''],
			['core', 'remote_files', ''],
			['core', 'remote_dav', ''],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataVerifyConfigKeyThrows')]
	public function testVerifyConfigKeyThrows(string $app, string $key, string $value): void {
		$this->expectException(\InvalidArgumentException::class);

		$api = $this->getInstance();
		$this->invokePrivate($api, 'verifyConfigKey', [$app, $key, $value]);
	}
}
