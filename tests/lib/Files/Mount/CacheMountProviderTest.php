<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Files\Mount;

use OC\Files\Mount\CacheMountProvider;
use OC\Files\Storage\StorageFactory;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;
use Test\TestCase;

class CacheMountProviderTestStream {
	public static $statCounter = 0;
	public static $mkdirCounter = 0;

	public $context;

	public function mkdir(string $path, int $mode, int $options): bool {
		self::$mkdirCounter++;
		return true;
	}

	public function url_stat(string $path, int $flags): array|false {
		self::$statCounter++;
		return false;
	}
}

class CacheMountProviderTest extends TestCase {
	private IConfig $config;
	private IUser $user;
	private IStorageFactory $storageFactory;

	protected function setUp(): void {
		$this->config = $this->createMock(IConfig::class);
		$this->user = $this->createMock(IUser::class);
		$this->storageFactory = new StorageFactory();
		stream_wrapper_register('cachemountprovidertest', CacheMountProviderTestStream::class);
	}

	protected function tearDown(): void {
		stream_wrapper_unregister('cachemountprovidertest');
	}

	public function testGetMountsForUser(): void {
		$provider = new CacheMountProvider($this->config);

		$this->assertCount(0, $provider->getMountsForUser($this->user, $this->storageFactory));
	}

	public function testGetMountsForUserCacheDir(): void {
		$this->config->expects($this->exactly(1))
			->method('getSystemValueString')
			->willReturnMap([
				['cache_path', '', 'cachemountprovidertest:////cache_path'],
			]);
		$this->user->method('getUID')
			->willReturn('bob');

		$provider = new CacheMountProvider($this->config);
		$mounts = $provider->getMountsForUser($this->user, $this->storageFactory);

		$this->assertCount(2, $mounts);
		$this->assertEquals(1, CacheMountProviderTestStream::$statCounter);
		$this->assertEquals(2, CacheMountProviderTestStream::$mkdirCounter);

		$cacheMountProvider = $mounts[0];
		$this->assertEquals('/bob/cache/', $cacheMountProvider->getMountPoint());

		$cacheStorage = $cacheMountProvider->getStorage();
		$this->assertEquals('local::cachemountprovidertest://cache_path/bob/', $cacheStorage->getId());

		$uploadsMountProvider = $mounts[1];
		$this->assertEquals('/bob/uploads/', $uploadsMountProvider->getMountPoint());

		$uploadsStorage = $uploadsMountProvider->getStorage();
		$this->assertEquals('local::cachemountprovidertest://cache_path/bob/uploads/', $uploadsStorage->getId());

		$cacheStorage->mkdir('foobar');
		$this->assertEquals(3, CacheMountProviderTestStream::$mkdirCounter);

		$uploadsStorage->mkdir('foobar');
		$this->assertEquals(4, CacheMountProviderTestStream::$mkdirCounter);
	}
}
