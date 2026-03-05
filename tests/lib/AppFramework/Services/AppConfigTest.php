<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\AppFramework\Services;

use OC\AppConfig as AppConfigCore;
use OC\AppFramework\Services\AppConfig;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig as IAppConfigCore;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AppConfigTest extends TestCase {
	private IConfig|MockObject $config;
	private IAppConfigCore|MockObject $appConfigCore;
	private AppConfig $appConfig;

	private const TEST_APPID = 'appconfig-test';

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->appConfigCore = $this->createMock(AppConfigCore::class);

		$this->appConfig = new AppConfig($this->config, $this->appConfigCore, self::TEST_APPID);
	}

	public function testGetAppKeys(): void {
		$expected = ['key1', 'key2', 'key3', 'key4', 'key5', 'key6', 'key7', 'test8'];
		$this->appConfigCore->expects($this->once())
			->method('getKeys')
			->with(self::TEST_APPID)
			->willReturn($expected);
		$this->assertSame($expected, $this->appConfig->getAppKeys());
	}


	/**
	 * @return array
	 * @see testHasAppKey
	 */
	public static function providerHasAppKey(): array {
		return [
			// lazy, expected
			[false, true],
			[true, true],
			[false, false],
			[true, false],
		];
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerHasAppKey')]
	public function testHasAppKey(bool $lazy, bool $expected): void {
		$key = 'key';
		$this->appConfigCore->expects($this->once())
			->method('hasKey')
			->with(self::TEST_APPID, $key, $lazy)
			->willReturn($expected);
		$this->assertSame($expected, $this->appConfig->hasAppKey($key, $lazy));
	}


	/**
	 * @return array
	 * @see testIsSensitive
	 */
	public static function providerIsSensitive(): array {
		return [
			// lazy, expected
			[false, true],
			[true, true],
			[false, false],
			[true, false],
		];
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerIsSensitive')]
	public function testIsSensitive(bool $lazy, bool $expected): void {
		$key = 'key';
		$this->appConfigCore->expects($this->once())
			->method('isSensitive')
			->with(self::TEST_APPID, $key, $lazy)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->isSensitive($key, $lazy));
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerIsSensitive')]
	public function testIsSensitiveException(bool $lazy, bool $expected): void {
		$key = 'unknown-key';
		$this->appConfigCore->expects($this->once())
			->method('isSensitive')
			->with(self::TEST_APPID, $key, $lazy)
			->willThrowException(new AppConfigUnknownKeyException());

		$this->expectException(AppConfigUnknownKeyException::class);
		$this->appConfig->isSensitive($key, $lazy);
	}

	/**
	 * @return array
	 * @see testIsLazy
	 */
	public static function providerIsLazy(): array {
		return [
			// expected
			[true],
			[false],
		];
	}

	/**
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerIsLazy')]
	public function testIsLazy(bool $expected): void {
		$key = 'key';
		$this->appConfigCore->expects($this->once())
			->method('isLazy')
			->with(self::TEST_APPID, $key)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->isLazy($key));
	}

	public function testIsLazyException(): void {
		$key = 'unknown-key';
		$this->appConfigCore->expects($this->once())
			->method('isLazy')
			->with(self::TEST_APPID, $key)
			->willThrowException(new AppConfigUnknownKeyException());

		$this->expectException(AppConfigUnknownKeyException::class);
		$this->appConfig->isLazy($key);
	}

	/**
	 * @return array
	 * @see testGetAllAppValues
	 */
	public static function providerGetAllAppValues(): array {
		return [
			// key, filtered
			['', false],
			['', true],
			['key', false],
			['key', true],
		];
	}

	/**
	 *
	 * @param string $key
	 * @param bool $filtered
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAllAppValues')]
	public function testGetAllAppValues(string $key, bool $filtered): void {
		$expected = [
			'key1' => 'value1',
			'key2' => 3,
			'key3' => 3.14,
			'key4' => true
		];

		$this->appConfigCore->expects($this->once())
			->method('getAllValues')
			->with(self::TEST_APPID, $key, $filtered)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->getAllAppValues($key, $filtered));
	}

	public function testSetAppValue(): void {
		$key = 'key';
		$value = 'value';
		$this->appConfigCore->expects($this->once())
			->method('setValueMixed')
			->with(self::TEST_APPID, $key, $value);

		$this->appConfig->setAppValue($key, $value);
	}

	/**
	 * @return array
	 * @see testSetAppValueString
	 * @see testSetAppValueStringException
	 * @see testSetAppValueInt
	 * @see testSetAppValueIntException
	 * @see testSetAppValueFloat
	 * @see testSetAppValueFloatException
	 * @see testSetAppValueArray
	 * @see testSetAppValueArrayException
	 */
	public static function providerSetAppValue(): array {
		return [
			// lazy, sensitive, expected
			[false, false, true],
			[false, true, true],
			[true, true, true],
			[true, false, true],
			[false, false, false],
			[false, true, false],
			[true, true, false],
			[true, false, false],
		];
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueString(bool $lazy, bool $sensitive, bool $expected): void {
		$key = 'key';
		$value = 'valueString';
		$this->appConfigCore->expects($this->once())
			->method('setValueString')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->setAppValueString($key, $value, $lazy, $sensitive));
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueStringException(bool $lazy, bool $sensitive): void {
		$key = 'key';
		$value = 'valueString';
		$this->appConfigCore->expects($this->once())
			->method('setValueString')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->setAppValueString($key, $value, $lazy, $sensitive);
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueInt(bool $lazy, bool $sensitive, bool $expected): void {
		$key = 'key';
		$value = 42;
		$this->appConfigCore->expects($this->once())
			->method('setValueInt')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->setAppValueInt($key, $value, $lazy, $sensitive));
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueIntException(bool $lazy, bool $sensitive): void {
		$key = 'key';
		$value = 42;
		$this->appConfigCore->expects($this->once())
			->method('setValueInt')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->setAppValueInt($key, $value, $lazy, $sensitive);
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueFloat(bool $lazy, bool $sensitive, bool $expected): void {
		$key = 'key';
		$value = 3.14;
		$this->appConfigCore->expects($this->once())
			->method('setValueFloat')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->setAppValueFloat($key, $value, $lazy, $sensitive));
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueFloatException(bool $lazy, bool $sensitive): void {
		$key = 'key';
		$value = 3.14;
		$this->appConfigCore->expects($this->once())
			->method('setValueFloat')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->setAppValueFloat($key, $value, $lazy, $sensitive);
	}

	/**
	 * @return array
	 * @see testSetAppValueBool
	 */
	public static function providerSetAppValueBool(): array {
		return [
			// lazy, expected
			[false, true],
			[false, false],
			[true, true],
			[true, false],
		];
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValueBool')]
	public function testSetAppValueBool(bool $lazy, bool $expected): void {
		$key = 'key';
		$value = true;
		$this->appConfigCore->expects($this->once())
			->method('setValueBool')
			->with(self::TEST_APPID, $key, $value, $lazy)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->setAppValueBool($key, $value, $lazy));
	}

	/**
	 * @param bool $lazy
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValueBool')]
	public function testSetAppValueBoolException(bool $lazy): void {
		$key = 'key';
		$value = true;
		$this->appConfigCore->expects($this->once())
			->method('setValueBool')
			->with(self::TEST_APPID, $key, $value, $lazy)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->setAppValueBool($key, $value, $lazy);
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueArray(bool $lazy, bool $sensitive, bool $expected): void {
		$key = 'key';
		$value = ['item' => true];
		$this->appConfigCore->expects($this->once())
			->method('setValueArray')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->setAppValueArray($key, $value, $lazy, $sensitive));
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $sensitive
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerSetAppValue')]
	public function testSetAppValueArrayException(bool $lazy, bool $sensitive): void {
		$key = 'key';
		$value = ['item' => true];
		$this->appConfigCore->expects($this->once())
			->method('setValueArray')
			->with(self::TEST_APPID, $key, $value, $lazy, $sensitive)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->setAppValueArray($key, $value, $lazy, $sensitive);
	}

	public function testGetAppValue(): void {
		$key = 'key';
		$value = 'value';
		$default = 'default';
		$this->appConfigCore->expects($this->once())
			->method('getValueMixed')
			->with(self::TEST_APPID, $key, $default)
			->willReturn($value);

		$this->assertSame($value, $this->appConfig->getAppValue($key, $default));
	}

	public function testGetAppValueDefault(): void {
		$key = 'key';
		$default = 'default';
		$this->appConfigCore->expects($this->once())
			->method('getValueMixed')
			->with(self::TEST_APPID, $key, $default)
			->willReturn($default);

		$this->assertSame($default, $this->appConfig->getAppValue($key, $default));
	}

	/**
	 * @return array
	 * @see testGetAppValueString
	 * @see testGetAppValueStringException
	 * @see testGetAppValueInt
	 * @see testGetAppValueIntException
	 * @see testGetAppValueFloat
	 * @see testGetAppValueFloatException
	 * @see testGetAppValueBool
	 * @see testGetAppValueBoolException
	 * @see testGetAppValueArray
	 * @see testGetAppValueArrayException
	 */
	public static function providerGetAppValue(): array {
		return [
			// lazy, exist
			[false, false],
			[false, true],
			[true, true],
			[true, false]
		];
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $exist
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueString(bool $lazy, bool $exist): void {
		$key = 'key';
		$value = 'valueString';
		$default = 'default';

		$expected = ($exist) ? $value : $default;
		$this->appConfigCore->expects($this->once())
			->method('getValueString')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->getAppValueString($key, $default, $lazy));
	}

	/**
	 * @param bool $lazy
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueStringException(bool $lazy): void {
		$key = 'key';
		$default = 'default';

		$this->appConfigCore->expects($this->once())
			->method('getValueString')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->getAppValueString($key, $default, $lazy);
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $exist
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueInt(bool $lazy, bool $exist): void {
		$key = 'key';
		$value = 42;
		$default = 17;

		$expected = ($exist) ? $value : $default;
		$this->appConfigCore->expects($this->once())
			->method('getValueInt')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->getAppValueInt($key, $default, $lazy));
	}

	/**
	 * @param bool $lazy
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueIntException(bool $lazy): void {
		$key = 'key';
		$default = 17;

		$this->appConfigCore->expects($this->once())
			->method('getValueInt')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->getAppValueInt($key, $default, $lazy);
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $exist
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueFloat(bool $lazy, bool $exist): void {
		$key = 'key';
		$value = 3.14;
		$default = 17.04;

		$expected = ($exist) ? $value : $default;
		$this->appConfigCore->expects($this->once())
			->method('getValueFloat')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->getAppValueFloat($key, $default, $lazy));
	}

	/**
	 * @param bool $lazy
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueFloatException(bool $lazy): void {
		$key = 'key';
		$default = 17.04;

		$this->appConfigCore->expects($this->once())
			->method('getValueFloat')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->getAppValueFloat($key, $default, $lazy);
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $exist
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueBool(bool $lazy, bool $exist): void {
		$key = 'key';
		$value = true;
		$default = false;

		$expected = ($exist) ? $value : $default; // yes, it can be simplified
		$this->appConfigCore->expects($this->once())
			->method('getValueBool')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->getAppValueBool($key, $default, $lazy));
	}

	/**
	 * @param bool $lazy
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueBoolException(bool $lazy): void {
		$key = 'key';
		$default = false;

		$this->appConfigCore->expects($this->once())
			->method('getValueBool')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->getAppValueBool($key, $default, $lazy);
	}

	/**
	 *
	 * @param bool $lazy
	 * @param bool $exist
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueArray(bool $lazy, bool $exist): void {
		$key = 'key';
		$value = ['item' => true];
		$default = [];

		$expected = ($exist) ? $value : $default;
		$this->appConfigCore->expects($this->once())
			->method('getValueArray')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willReturn($expected);

		$this->assertSame($expected, $this->appConfig->getAppValueArray($key, $default, $lazy));
	}

	/**
	 * @param bool $lazy
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('providerGetAppValue')]
	public function testGetAppValueArrayException(bool $lazy): void {
		$key = 'key';
		$default = [];

		$this->appConfigCore->expects($this->once())
			->method('getValueArray')
			->with(self::TEST_APPID, $key, $default, $lazy)
			->willThrowException(new AppConfigTypeConflictException());

		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->getAppValueArray($key, $default, $lazy);
	}

	public function testDeleteAppValue(): void {
		$key = 'key';
		$this->appConfigCore->expects($this->once())
			->method('deleteKey')
			->with(self::TEST_APPID, $key);

		$this->appConfig->deleteAppValue($key);
	}

	public function testDeleteAppValues(): void {
		$this->appConfigCore->expects($this->once())
			->method('deleteApp')
			->with(self::TEST_APPID);

		$this->appConfig->deleteAppValues();
	}
}
