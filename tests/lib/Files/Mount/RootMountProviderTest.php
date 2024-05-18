<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Files\Mount;

use OC\Files\Mount\RootMountProvider;
use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\S3;
use OC\Files\Storage\LocalRootStorage;
use OC\Files\Storage\StorageFactory;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
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
		$provider = new RootMountProvider($config, $this->createMock(LoggerInterface::class));
		return $provider;
	}

	public function testLocal() {
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

	public function testObjectStore() {
		$provider = $this->getProvider([
			'objectstore' => [
				"class" => "OC\Files\ObjectStore\S3",
				"arguments" => [
					"bucket" => "nextcloud",
					"autocreate" => true,
					"key" => "minio",
					"secret" => "minio123",
					"hostname" => "localhost",
					"port" => 9000,
					"use_ssl" => false,
					"use_path_style" => true,
					"uploadPartSize" => 52428800,
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

	public function testObjectStoreMultiBucket() {
		$provider = $this->getProvider([
			'objectstore_multibucket' => [
				"class" => "OC\Files\ObjectStore\S3",
				"arguments" => [
					"bucket" => "nextcloud",
					"autocreate" => true,
					"key" => "minio",
					"secret" => "minio123",
					"hostname" => "localhost",
					"port" => 9000,
					"use_ssl" => false,
					"use_path_style" => true,
					"uploadPartSize" => 52428800,
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
