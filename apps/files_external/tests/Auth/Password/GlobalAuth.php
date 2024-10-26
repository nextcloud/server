<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Auth\Password;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_external\Lib\StorageConfig;
use OCP\IL10N;
use OCP\Security\ICredentialsManager;
use Test\TestCase;

class GlobalAuthTest extends TestCase {
	/**
	 * @var IL10N|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $l10n;

	/**
	 * @var ICredentialsManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $credentialsManager;

	/**
	 * @var GlobalAuth
	 */
	private $instance;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->credentialsManager = $this->createMock(ICredentialsManager::class);
		$this->instance = new GlobalAuth($this->l10n, $this->credentialsManager);
	}

	private function getStorageConfig($type, $config = []) {
		/** @var \OCA\Files_External\Lib\StorageConfig|\PHPUnit\Framework\MockObject\MockObject $storageConfig */
		$storageConfig = $this->createMock(StorageConfig::class);
		$storageConfig->expects($this->any())
			->method('getType')
			->willReturn($type);
		$storageConfig->expects($this->any())
			->method('getBackendOptions')
			->willReturnCallback(function () use (&$config) {
				return $config;
			});
		$storageConfig->expects($this->any())
			->method('getBackendOption')
			->willReturnCallback(function ($key) use (&$config) {
				return $config[$key];
			});
		$storageConfig->expects($this->any())
			->method('setBackendOption')
			->willReturnCallback(function ($key, $value) use (&$config): void {
				$config[$key] = $value;
			});

		return $storageConfig;
	}

	public function testNoCredentials(): void {
		$this->credentialsManager->expects($this->once())
			->method('retrieve')
			->willReturn(null);

		$storage = $this->getStorageConfig(StorageConfig::MOUNT_TYPE_ADMIN);

		$this->instance->manipulateStorageConfig($storage);
		$this->assertEquals([], $storage->getBackendOptions());
	}

	public function testSavedCredentials(): void {
		$this->credentialsManager->expects($this->once())
			->method('retrieve')
			->willReturn([
				'user' => 'a',
				'password' => 'b'
			]);

		$storage = $this->getStorageConfig(StorageConfig::MOUNT_TYPE_ADMIN);

		$this->instance->manipulateStorageConfig($storage);
		$this->assertEquals([
			'user' => 'a',
			'password' => 'b'
		], $storage->getBackendOptions());
	}


	public function testNoCredentialsPersonal(): void {
		$this->expectException(InsufficientDataForMeaningfulAnswerException::class);

		$this->credentialsManager->expects($this->never())
			->method('retrieve');

		$storage = $this->getStorageConfig(StorageConfig::MOUNT_TYPE_PERSONAL);

		$this->instance->manipulateStorageConfig($storage);
		$this->assertEquals([], $storage->getBackendOptions());
	}
}
