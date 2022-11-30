<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Mount;

use OC\Files\Mount\RootMountProvider;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\ObjectStore\S3;
use OC\Files\Storage\LocalRootStorage;
use OC\Files\Storage\StorageFactory;
use OCP\App\IAppManager;
use OCP\IConfig;
use Test\TestCase;

/**
 * @group DB
 */
class RootMountProviderTest extends TestCase {
	private StorageFactory $loader;

	protected function setUp(): void {
		parent::setUp();

		$this->loader = new StorageFactory();
	}

	private function getConfig(array $systemConfig): IConfig {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValue')
			->willReturnCallback(function (string $key, $default) use ($systemConfig) {
				return $systemConfig[$key] ?? $default;
			});
		return $config;
	}

	private function getProvider(array $systemConfig): RootMountProvider {
		$config = $this->getConfig($systemConfig);
		$objectStoreConfig = new PrimaryObjectStoreConfig($config, $this->createMock(IAppManager::class));
		return new RootMountProvider($objectStoreConfig, $config);
	}

	public function testLocal(): void {
		$provider = $this->getProvider([
			'datadirectory' => '/data',
		]);
		$mounts = $provider->getRootMounts($this->loader);
		$this->assertCount(1, $mounts);
		$mount = $mounts[0];
		$this->assertEquals('/', $mount->getMountPoint());
		/** @var LocalRootStorage $storage */
		$storage = $mount->getStorage();
		$this->assertInstanceOf(LocalRootStorage::class, $storage);
		$this->assertEquals('/data/', $storage->getSourcePath(''));
	}

	public function testObjectStore(): void {
		$provider = $this->getProvider([
			'objectstore' => [
				'class' => "OC\Files\ObjectStore\S3",
				'arguments' => [
					'bucket' => 'nextcloud',
					'autocreate' => true,
					'key' => 'minio',
					'secret' => 'minio123',
					'hostname' => 'localhost',
					'port' => 9000,
					'use_ssl' => false,
					'use_path_style' => true,
					'uploadPartSize' => 52428800,
				],
			],
		]);
		$mounts = $provider->getRootMounts($this->loader);
		$this->assertCount(1, $mounts);
		$mount = $mounts[0];
		$this->assertEquals('/', $mount->getMountPoint());
		/** @var ObjectStoreStorage $storage */
		$storage = $mount->getStorage();
		$this->assertInstanceOf(ObjectStoreStorage::class, $storage);

		$class = new \ReflectionClass($storage);
		$prop = $class->getProperty('objectStore');
		$prop->setAccessible(true);
		/** @var S3 $objectStore */
		$objectStore = $prop->getValue($storage);
		$this->assertEquals('nextcloud', $objectStore->getBucket());
	}

	public function testObjectStoreMultiBucket(): void {
		$provider = $this->getProvider([
			'objectstore_multibucket' => [
				'class' => "OC\Files\ObjectStore\S3",
				'arguments' => [
					'bucket' => 'nextcloud',
					'autocreate' => true,
					'key' => 'minio',
					'secret' => 'minio123',
					'hostname' => 'localhost',
					'port' => 9000,
					'use_ssl' => false,
					'use_path_style' => true,
					'uploadPartSize' => 52428800,
				],
			],
		]);
		$mounts = $provider->getRootMounts($this->loader);
		$this->assertCount(1, $mounts);
		$mount = $mounts[0];
		$this->assertEquals('/', $mount->getMountPoint());
		/** @var ObjectStoreStorage $storage */
		$storage = $mount->getStorage();
		$this->assertInstanceOf(ObjectStoreStorage::class, $storage);

		$class = new \ReflectionClass($storage);
		$prop = $class->getProperty('objectStore');
		$prop->setAccessible(true);
		/** @var S3 $objectStore */
		$objectStore = $prop->getValue($storage);
		$this->assertEquals('nextcloud0', $objectStore->getBucket());
	}
}
