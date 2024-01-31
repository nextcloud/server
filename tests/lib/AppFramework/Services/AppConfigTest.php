<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024, Maxence Lange <maxence@artificial-owl.com
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\AppFramework\Services;

use OC\AppFramework\Services\AppConfig;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig as IAppConfigCore;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AppConfigTest extends TestCase {
	private IConfig|MockObject $config;
	private IAppConfigCore $appConfigCore;
	private AppConfig $appConfig;

	private const TEST_APPID = 'appconfig-test';

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);

		$this->appConfigCore = \OCP\Server::get(IAppConfigCore::class);

		// we reset previous test entries and initiate some value in core appconfig
		$this->appConfigCore->deleteApp(self::TEST_APPID);
		$this->appConfigCore->setValueString(self::TEST_APPID, 'key1', 'value1');
		$this->appConfigCore->setValueString(self::TEST_APPID, 'key2', 'value0', sensitive: true);
		$this->appConfigCore->setValueString(self::TEST_APPID, 'key3', 'value0', true, true);
		$this->appConfigCore->setValueInt(self::TEST_APPID, 'key4', 3, true);
		$this->appConfigCore->setValueFloat(self::TEST_APPID, 'key5', 3.14, true);
		$this->appConfigCore->setValueBool(self::TEST_APPID, 'key6', true);
		$this->appConfigCore->setValueArray(self::TEST_APPID, 'key7', ['test1' => 1, 'test2' => 2]);
		$this->appConfigCore->setValueBool(self::TEST_APPID, 'test8', true);

		$this->appConfigCore->clearCache();
		$this->appConfig = new AppConfig($this->config, $this->appConfigCore, self::TEST_APPID);
	}

	public function testGetAppKeys(): void {
		$expected = ['key1', 'key2', 'key3', 'key4', 'key5', 'key6', 'key7', 'test8'];
		$this->assertSame($expected, $this->appConfig->getAppKeys());
	}

	public function testHasExistingAppKey(): void {
		$this->assertSame(true, $this->appConfig->hasAppKey('key1'));
		$this->assertSame(true, $this->appConfig->hasAppKey('key4', true));
		$this->appConfigCore->clearCache();
		$this->assertSame(true, $this->appConfig->hasAppKey('key5', null));
		$this->assertSame(true, $this->appConfig->hasAppKey('test8'));
		$this->assertSame(false, $this->appConfig->hasAppKey('inexisting-key'));
	}

	public function testIsSensitive(): void {
		$this->assertSame(false, $this->appConfig->isSensitive('key1'));
		$this->assertSame(true, $this->appConfig->isSensitive('key2'));

		try {
			$this->assertSame(
				false, $this->appConfig->isSensitive('key3'),
				'should throw exception, or call clearCache before this test'
			);
			$this->assertSame(true, false, 'not supposed to happen, key3 is set as lazy and sensitive');
		} catch (AppConfigUnknownKeyException $e) {
			$this->assertSame(true, true);
		}

		$this->assertSame(true, $this->appConfig->isSensitive('key3', true));
		$this->assertSame(false, $this->appConfig->isSensitive('key4', true));
	}

	public function testIsLazy(): void {
		$this->assertSame(false, $this->appConfig->isLazy('key1'));
		$this->assertSame(false, $this->appConfig->isLazy('key2'));
		$this->assertSame(true, $this->appConfig->isLazy('key3'));
		$this->assertSame(true, $this->appConfig->isLazy('key4'));
		$this->assertSame(true, $this->appConfig->isLazy('key5'));
		$this->assertSame(false, $this->appConfig->isLazy('key6'));
		$this->assertSame(false, $this->appConfig->isLazy('key7'));
		$this->assertSame(false, $this->appConfig->isLazy('test8'));
	}


	// TODO: fix this in core: getAllAppValues() should returns values based on their types instead of all string
//	public function testGetAllAppValues(): void {
//		$this->assertSame(
//			[
//				'key1' => 'value1',
//				'key2' => 'value0',
//				'key6' => 1,
//				'key7' => [
//					'test1' => 1,
//					'test2' => 2
//				],
//				'test8' => 1,
//				'key3' => 'value0',
//				'key4' => 3,
//				'key5' => 3.14
//			],
//			$this->appConfig->getAllAppValues()
//		);
//	}


	public function testSetAppValue(): void {
		$this->appConfig->setAppValue('old1', 'newvalue');
		$this->assertSame('newvalue', $this->appConfigCore->getValueString(self::TEST_APPID, 'old1', 'default'));
	}

	public function testSetAppValueString(): void {
		$this->assertSame('value1', $this->appConfigCore->getValueString(self::TEST_APPID, 'key1', 'default'));
		$this->assertSame(false, $this->appConfig->setAppValueString('key1', 'value1'));
		$this->assertSame(true, $this->appConfig->setAppValueString('key1', 'newvalue1'));
		$this->assertSame('newvalue1', $this->appConfigCore->getValueString(self::TEST_APPID, 'key1', 'default'));
	}

	public function testSetAppValueInt(): void {
		$this->assertSame(3, $this->appConfigCore->getValueInt(self::TEST_APPID, 'key4', lazy: true));
		$this->assertSame(false, $this->appConfig->setAppValueInt('key4', 3, true));
		$this->assertSame(true, $this->appConfig->setAppValueInt('key4', 4, true));
		$this->assertSame(4, $this->appConfigCore->getValueInt(self::TEST_APPID, 'key4', lazy: true));
	}

	public function testSetAppValueFloat(): void {
		$this->assertSame(3.14, $this->appConfigCore->getValueFloat(self::TEST_APPID, 'key5', lazy: true));
		$this->assertSame(false, $this->appConfig->setAppValueFloat('key5', 3.14, true));
		$this->assertSame(true, $this->appConfig->setAppValueFloat('key5', 4.17, true));
		$this->assertSame(4.17, $this->appConfigCore->getValueFloat(self::TEST_APPID, 'key5', lazy: true));
	}

	public function testSetAppValueBool(): void {
		$this->assertSame(true, $this->appConfigCore->getValueBool(self::TEST_APPID, 'key6', false));
		$this->assertSame(false, $this->appConfig->setAppValueBool('key6', true));
		$this->assertSame(true, $this->appConfig->setAppValueBool('key6', false));
		$this->assertSame(false, $this->appConfigCore->getValueBool(self::TEST_APPID, 'key6', true));
	}

	public function testSetAppValueArray(): void {
		$this->assertSame(['test1' => 1, 'test2' => 2], $this->appConfigCore->getValueArray(self::TEST_APPID, 'key7', []));
		$this->assertSame(false, $this->appConfig->setAppValueArray('key7', ['test1' => 1, 'test2' => 2]));
		$this->assertSame(true, $this->appConfig->setAppValueArray('key7', ['test3' => 0]));
		$this->assertSame(['test3' => 0], $this->appConfigCore->getValueArray(self::TEST_APPID, 'key7', []));
	}

	public function testGetAppValue(): void {
		$this->appConfigCore->setValueMixed(self::TEST_APPID, 'old1', 'newvalue1');
		$this->assertSame('newvalue1', $this->appConfig->getAppValue('old1', 'default'));
	}

	public function testGetAppValueString(): void {
		$this->appConfigCore->setValueString(self::TEST_APPID, 'key1', 'value1new');
		$this->appConfigCore->setValueString(self::TEST_APPID, 'key3', 'value3new', lazy: true);
		$this->assertSame('value1new', $this->appConfig->getAppValueString('key1', 'default'));
		$this->assertSame('default', $this->appConfig->getAppValueString('key3', 'default'));
		$this->assertSame('value3new', $this->appConfig->getAppValueString('key3', 'default', lazy: true));
	}

	public function testGetAppValueInt(): void {
		$this->appConfigCore->setValueInt(self::TEST_APPID, 'key4', 14, true);
		$this->assertSame(14, $this->appConfig->getAppValueInt('key4', 0, true));
	}

	public function testGetAppValueFloat(): void {
		$this->appConfigCore->setValueFloat(self::TEST_APPID, 'key5', 12.34, true);
		$this->assertSame(12.34, $this->appConfig->getAppValueFloat('key5', 0, true));
	}

	public function testGetAppValueBool(): void {
		$this->appConfigCore->setValueBool(self::TEST_APPID, 'key6', true);
		$this->assertSame(true, $this->appConfig->getAppValueBool('key6', false));
		$this->appConfigCore->setValueBool(self::TEST_APPID, 'key6', false);
		$this->assertSame(false, $this->appConfig->getAppValueBool('key6', true));
	}

	public function testGetAppValueArray(): void {
		$this->appConfigCore->setValueArray(self::TEST_APPID, 'key7', ['test' => 'done']);
		$this->assertSame(['test' => 'done'], $this->appConfig->getAppValueArray('key7', []));
	}

	public function testDeleteAppValue(): void {
		$this->assertSame(true, $this->appConfigCore->hasKey(self::TEST_APPID, 'test8'));
		$this->appConfig->deleteAppValue('test8');
		$this->assertSame(false, $this->appConfigCore->hasKey(self::TEST_APPID, 'test8'));
		$this->assertSame(['key1', 'key2', 'key3', 'key4', 'key5', 'key6', 'key7'], $this->appConfigCore->getKeys(self::TEST_APPID));
	}

	public function testDeleteAppValues(): void {
		$this->assertSame(['key1', 'key2', 'key3', 'key4', 'key5', 'key6', 'key7', 'test8'], $this->appConfigCore->getKeys(self::TEST_APPID));
		$this->appConfig->deleteAppValues();
		$this->assertSame([], $this->appConfigCore->getKeys(self::TEST_APPID));
	}
}
