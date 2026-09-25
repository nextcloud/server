<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Encryption;

use OC\Encryption\Exceptions\ModuleAlreadyExistsException;
use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OC\Encryption\Manager;
use OC\Encryption\Util;
use OC\Memcache\ArrayCache;
use OCP\Encryption\IEncryptionModule;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	private Manager $manager;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private LoggerInterface&MockObject $logger;
	private IL10N&MockObject $l10n;
	private IRootFolder&MockObject $rootFolder;
	private Util&MockObject $util;
	private ArrayCache&MockObject $arrayCache;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->util = $this->createMock(Util::class);
		$this->arrayCache = $this->createMock(ArrayCache::class);
		$this->manager = new Manager($this->config, $this->appConfig, $this->logger, $this->l10n, $this->rootFolder, $this->util, $this->arrayCache);
	}

	public function testManagerIsDisabled(): void {
		$this->assertFalse($this->manager->isEnabled());
	}

	public function testManagerIsDisabledIfEnabledButNoModules(): void {
		$this->config->expects($this->any())->method('getAppValue')->willReturn(true);
		$this->assertFalse($this->manager->isEnabled());
	}

	public function testManagerIsDisabledIfDisabledButModules(): void {
		$this->config->expects($this->any())->method('getAppValue')->willReturn(false);
		$em = $this->createMock(IEncryptionModule::class);
		$em->expects($this->any())->method('getId')->willReturn('id');
		$em->expects($this->any())->method('getDisplayName')->willReturn('TestDummyModule0');
		$this->manager->registerEncryptionModule('id', 'TestDummyModule0', function () use ($em) {
			return $em;
		});
		$this->assertFalse($this->manager->isEnabled());
	}

	public function testManagerIsEnabled(): void {
		$this->appConfig->expects($this->any())->method('getValueBool')->with('core', 'encryption_enabled')->willReturn(true);

		$this->config->expects($this->any())->method('getSystemValueBool')->willReturn(true);
		$result = $this->manager->isEnabled();

		$this->assertTrue($result);
	}

	public function testModuleRegistration() {
		$this->appConfig->expects($this->any())->method('getValueBool')->willReturn(true);

		$this->addNewEncryptionModule($this->manager, 0);
		$this->assertCount(1, $this->manager->getEncryptionModules());

		return $this->manager;
	}

	#[\PHPUnit\Framework\Attributes\Depends('testModuleRegistration')]
	public function testModuleReRegistration($manager): void {
		$this->expectException(ModuleAlreadyExistsException::class);
		$this->expectExceptionMessage('Id "ID0" already used by encryption module "TestDummyModule0"');

		$this->addNewEncryptionModule($manager, 0);
	}

	public function testModuleUnRegistration(): void {
		$this->appConfig->expects($this->any())->method('getValueBool')->willReturn(true);
		$this->addNewEncryptionModule($this->manager, 0);
		$this->assertCount(1, $this->manager->getEncryptionModules());

		$this->manager->unregisterEncryptionModule('ID0');
		$this->assertEmpty($this->manager->getEncryptionModules());
	}

	public function testGetEncryptionModuleUnknown(): void {
		$this->expectException(ModuleDoesNotExistsException::class);
		$this->expectExceptionMessage('Module with ID: unknown does not exist.');

		$this->appConfig->expects($this->any())->method('getValueBool')->willReturn(true);
		$this->addNewEncryptionModule($this->manager, 0);
		$this->assertCount(1, $this->manager->getEncryptionModules());
		$this->manager->getEncryptionModule('unknown');
	}

	public function testGetEncryptionModuleEmpty(): void {
		global $defaultId;
		$defaultId = '';

		$this->appConfig->expects($this->any())
			->method('getValueString')
			->with('core', 'default_encryption_module')
			->willReturnCallback(function () {
				global $defaultId;
				return $defaultId;
			});

		$this->addNewEncryptionModule($this->manager, 0);
		$this->assertCount(1, $this->manager->getEncryptionModules());
		$this->addNewEncryptionModule($this->manager, 1);
		$this->assertCount(2, $this->manager->getEncryptionModules());

		// Should return the default module
		$defaultId = 'ID0';
		$this->assertEquals('ID0', $this->manager->getEncryptionModule()->getId());
		$defaultId = 'ID1';
		$this->assertEquals('ID1', $this->manager->getEncryptionModule()->getId());
	}

	public function testGetEncryptionModule(): void {
		global $defaultId;
		$defaultId = '';

		$this->appConfig->expects($this->any())
			->method('getValueString')
			->with('core', 'default_encryption_module')
			->willReturnCallback(function () {
				global $defaultId;
				return $defaultId;
			});

		$this->addNewEncryptionModule($this->manager, 0);
		$defaultId = 'ID0';
		$this->assertCount(1, $this->manager->getEncryptionModules());

		$en0 = $this->manager->getEncryptionModule('ID0');
		$this->assertEquals('ID0', $en0->getId());

		$en0 = self::invokePrivate($this->manager, 'getDefaultEncryptionModule');
		$this->assertEquals('ID0', $en0->getId());

		$this->assertEquals('ID0', $this->manager->getDefaultEncryptionModuleId());
	}

	public function testSetDefaultEncryptionModule(): void {
		global $defaultId;
		$defaultId = '';

		$this->appConfig->expects($this->any())
			->method('getValueString')
			->with('core', 'default_encryption_module')
			->willReturnCallback(function () {
				global $defaultId;
				return $defaultId;
			});

		$this->addNewEncryptionModule($this->manager, 0);
		$this->assertCount(1, $this->manager->getEncryptionModules());
		$this->addNewEncryptionModule($this->manager, 1);
		$this->assertCount(2, $this->manager->getEncryptionModules());

		// Default module is the first we set
		$defaultId = 'ID0';
		$this->assertEquals('ID0', $this->manager->getDefaultEncryptionModuleId());

		// Set to an existing module
		$this->appConfig->expects($this->once())
			->method('setValueString')
			->with('core', 'default_encryption_module', 'ID1');
		$this->assertTrue($this->manager->setDefaultEncryptionModule('ID1'));
		$defaultId = 'ID1';
		$this->assertEquals('ID1', $this->manager->getDefaultEncryptionModuleId());

		// Set to an unexisting module
		$this->assertFalse($this->manager->setDefaultEncryptionModule('ID2'));
		$this->assertEquals('ID1', $this->manager->getDefaultEncryptionModuleId());
	}

	//	/**
	//	 * @expectedException \OC\Encryption\Exceptions\ModuleAlreadyExistsException
	//	 * @expectedExceptionMessage Id "0" already used by encryption module "TestDummyModule0"
	//	 */
	//	public function testModuleRegistration() {
	//		$config = $this->createMock(IConfig::class);
	//		$config->expects($this->any())->method('getSystemValueBool')->willReturn(true);
	//		$em = $this->createMock(IEncryptionModule::class);
	//		$em->expects($this->any())->method('getId')->willReturn(0);
	//		$em->expects($this->any())->method('getDisplayName')->willReturn('TestDummyModule0');
	//		$m = new Manager($config);
	//		$m->registerEncryptionModule($em);
	//		$this->assertTrue($m->isEnabled());
	//		$m->registerEncryptionModule($em);
	//	}
	//
	//	public function testModuleUnRegistration() {
	//		$config = $this->createMock(IConfig::class);
	//		$config->expects($this->any())->method('getSystemValueBool')->willReturn(true);
	//		$em = $this->createMock(IEncryptionModule::class);
	//		$em->expects($this->any())->method('getId')->willReturn(0);
	//		$em->expects($this->any())->method('getDisplayName')->willReturn('TestDummyModule0');
	//		$m = new Manager($config);
	//		$m->registerEncryptionModule($em);
	//		$this->assertTrue($m->isEnabled());
	//		$m->unregisterEncryptionModule($em);
	//		$this->assertFalse($m->isEnabled());
	//	}
	//
	//	/**
	//	 * @expectedException \OC\Encryption\Exceptions\ModuleDoesNotExistsException
	//	 * @expectedExceptionMessage Module with ID: unknown does not exist.
	//	 */
	//	public function testGetEncryptionModuleUnknown() {
	//		$config = $this->createMock(IConfig::class);
	//		$config->expects($this->any())->method('getSystemValueBool')->willReturn(true);
	//		$em = $this->createMock(IEncryptionModule::class);
	//		$em->expects($this->any())->method('getId')->willReturn(0);
	//		$em->expects($this->any())->method('getDisplayName')->willReturn('TestDummyModule0');
	//		$m = new Manager($config);
	//		$m->registerEncryptionModule($em);
	//		$this->assertTrue($m->isEnabled());
	//		$m->getEncryptionModule('unknown');
	//	}
	//
	//	public function testGetEncryptionModule() {
	//		$config = $this->createMock(IConfig::class);
	//		$config->expects($this->any())->method('getSystemValueBool')->willReturn(true);
	//		$em = $this->createMock(IEncryptionModule::class);
	//		$em->expects($this->any())->method('getId')->willReturn(0);
	//		$em->expects($this->any())->method('getDisplayName')->willReturn('TestDummyModule0');
	//		$m = new Manager($config);
	//		$m->registerEncryptionModule($em);
	//		$this->assertTrue($m->isEnabled());
	//		$en0 = $m->getEncryptionModule(0);
	//		$this->assertEquals(0, $en0->getId());
	//	}

	protected function addNewEncryptionModule(Manager $manager, $id) {
		$encryptionModule = $this->createMock(IEncryptionModule::class);
		$encryptionModule->expects($this->any())
			->method('getId')
			->willReturn('ID' . $id);
		$encryptionModule->expects($this->any())
			->method('getDisplayName')
			->willReturn('TestDummyModule' . $id);
		/** @var IEncryptionModule $encryptionModule */
		$manager->registerEncryptionModule('ID' . $id, 'TestDummyModule' . $id, function () use ($encryptionModule) {
			return $encryptionModule;
		});
	}
}
