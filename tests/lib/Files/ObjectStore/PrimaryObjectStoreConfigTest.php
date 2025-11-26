<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Files\ObjectStore;

use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\ObjectStore\StorageObjectStore;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PrimaryObjectStoreConfigTest extends TestCase {
	private array $systemConfig = [];
	private array $userConfig = [];
	private IConfig&MockObject $config;
	private IAppManager&MockObject $appManager;
	private PrimaryObjectStoreConfig $objectStoreConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->systemConfig = [];
		$this->config = $this->createMock(IConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->config->method('getSystemValue')
			->willReturnCallback(function ($key, $default = '') {
				if (isset($this->systemConfig[$key])) {
					return $this->systemConfig[$key];
				} else {
					return $default;
				}
			});
		$this->config->method('getUserValue')
			->willReturnCallback(function ($userId, $appName, $key, $default = '') {
				if (isset($this->userConfig[$userId][$appName][$key])) {
					return $this->userConfig[$userId][$appName][$key];
				} else {
					return $default;
				}
			});
		$this->config->method('setUserValue')
			->willReturnCallback(function ($userId, $appName, $key, $value): void {
				$this->userConfig[$userId][$appName][$key] = $value;
			});

		$this->objectStoreConfig = new PrimaryObjectStoreConfig($this->config, $this->appManager);
	}

	private function getUser(string $uid): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($uid);
		return $user;
	}

	private function setConfig(string $key, $value) {
		$this->systemConfig[$key] = $value;
	}

	public function testNewUserGetsDefault() {
		$this->setConfig('objectstore', [
			'default' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
				],
			],
		]);

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('test'));
		$this->assertEquals('server1', $result['arguments']['host']);

		$this->assertEquals('server1', $this->config->getUserValue('test', 'homeobjectstore', 'objectstore', null));
	}

	public function testExistingUserKeepsStorage() {
		// setup user with `server1` as storage
		$this->testNewUserGetsDefault();

		$this->setConfig('objectstore', [
			'default' => 'server2',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'bucket' => '1',
				],
			],
			'server2' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server2',
					'bucket' => '2',
				],
			],
		]);

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('test'));
		$this->assertEquals('server1', $result['arguments']['host']);

		$this->assertEquals('server1', $this->config->getUserValue('test', 'homeobjectstore', 'objectstore', null));

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('other-user'));
		$this->assertEquals('server2', $result['arguments']['host']);
	}

	public function testNestedAliases() {
		$this->setConfig('objectstore', [
			'default' => 'a1',
			'a1' => 'a2',
			'a2' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'bucket' => '1',
				],
			],
		]);
		$this->assertEquals('server1', $this->objectStoreConfig->resolveAlias('default'));
	}

	public function testMultibucketChangedConfig() {
		$this->setConfig('objectstore', [
			'default' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'multibucket' => true,
					'num_buckets' => 8,
					'bucket' => 'bucket1-'
				],
			],
		]);

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('test'));
		$this->assertEquals('server1', $result['arguments']['host']);
		$this->assertEquals('bucket1-7', $result['arguments']['bucket']);

		$this->setConfig('objectstore', [
			'default' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'multibucket' => true,
					'num_buckets' => 64,
					'bucket' => 'bucket1-'
				],
			],
		]);

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('test'));
		$this->assertEquals('server1', $result['arguments']['host']);
		$this->assertEquals('bucket1-7', $result['arguments']['bucket']);

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('test-foo'));
		$this->assertEquals('server1', $result['arguments']['host']);
		$this->assertEquals('bucket1-40', $result['arguments']['bucket']);

		$this->setConfig('objectstore', [
			'default' => 'server2',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'multibucket' => true,
					'num_buckets' => 64,
					'bucket' => 'bucket1-'
				],
			],
			'server2' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server2',
					'multibucket' => true,
					'num_buckets' => 16,
					'bucket' => 'bucket2-'
				],
			],
		]);

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('test'));
		$this->assertEquals('server1', $result['arguments']['host']);
		$this->assertEquals('bucket1-7', $result['arguments']['bucket']);

		$result = $this->objectStoreConfig->getObjectStoreConfigForUser($this->getUser('test-bar'));
		$this->assertEquals('server2', $result['arguments']['host']);
		$this->assertEquals('bucket2-4', $result['arguments']['bucket']);
	}

	public function testMultibucketOldConfig() {
		$this->setConfig('objectstore_multibucket', [
			'class' => StorageObjectStore::class,
			'arguments' => [
				'host' => 'server1',
				'multibucket' => true,
				'num_buckets' => 8,
				'bucket' => 'bucket-'
			],
		]);
		$configs = $this->objectStoreConfig->getObjectStoreConfigs();
		$this->assertEquals([
			'default' => 'server1',
			'root' => 'server1',
			'preview' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'multibucket' => true,
					'num_buckets' => 8,
					'bucket' => 'bucket-'
				],
			],
		], $configs);
	}

	public function testSingleObjectStore() {
		$this->setConfig('objectstore', [
			'class' => StorageObjectStore::class,
			'arguments' => [
				'host' => 'server1',
			],
		]);
		$configs = $this->objectStoreConfig->getObjectStoreConfigs();
		$this->assertEquals([
			'default' => 'server1',
			'root' => 'server1',
			'preview' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'multibucket' => false,
				],
			],
		], $configs);
	}

	public function testRoot() {
		$this->setConfig('objectstore', [
			'default' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'bucket' => '1',
				],
			],
			'server2' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server2',
					'bucket' => '2',
				],
			],
		]);

		$result = $this->objectStoreConfig->getObjectStoreConfigForRoot();
		$this->assertEquals('server1', $result['arguments']['host']);

		$this->setConfig('objectstore', [
			'default' => 'server1',
			'root' => 'server2',
			'preview' => 'server1',
			'server1' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server1',
					'bucket' => '1',
				],
			],
			'server2' => [
				'class' => StorageObjectStore::class,
				'arguments' => [
					'host' => 'server2',
					'bucket' => '2',
				],
			],
		]);

		$result = $this->objectStoreConfig->getObjectStoreConfigForRoot();
		$this->assertEquals('server2', $result['arguments']['host']);
	}
}
