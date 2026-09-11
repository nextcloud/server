<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Cache;

use OC\Cache\File;
use OC\Files\Storage\Temporary;
use OCP\Files\ISetupManager;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Storage\IStorage;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\Server;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class FileCacheTest
 *
 *
 * @package Test\Cache
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class FileCacheTest extends TestCache {
	use UserTrait;
	use MountProviderTrait;

	private IStorage $storage;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$user = $this->createUser('test', 'test');

		$userSession = Server::get(IUserSession::class);
		$userSession->setUser($user);

		/** @var IMountManager $manager */
		$manager = Server::get(IMountManager::class);
		$manager->removeMount('/test');

		$this->storage = new Temporary([]);
		$this->registerMount($user->getUID(), $this->storage, '/' . $user->getUID() . '/cache/');

		$this->instance = new File();

		// forces creation of cache folder for subsequent tests
		$this->instance->set('hack', 'hack');
	}

	#[\Override]
	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->clear();
			$this->instance = null;
		}

		Server::get(ISetupManager::class)->tearDown();

		parent::tearDown();
	}

	public function testGarbageCollectOldKeys(): void {
		$this->instance->set('key1', 'value1');

		$this->assertTrue($this->storage->file_exists('key1'));
		$this->storage->getCache()->put('key1', ['mtime' => 100]);

		$this->instance->gc();
		$this->assertFalse($this->storage->file_exists('key1'));
	}

	public function testGarbageCollectLeaveRecentKeys(): void {
		$this->instance->set('key1', 'value1');

		$this->assertTrue($this->storage->file_exists('key1'));
		$this->storage->getCache()->put('key1', ['mtime' => time() + 3600]);

		$this->instance->gc();

		$this->assertTrue($this->storage->file_exists('key1'));
	}

	public function testGarbageCollectIgnoreLockedKeys(): void {
		$lockingProvider = \OC::$server->get(ILockingProvider::class);

		$this->instance->set('key1', 'value1');
		$this->storage->getCache()->put('key1', ['mtime' => 100]);
		$this->instance->set('key2', 'value2');
		$this->storage->getCache()->put('key2', ['mtime' => 100]);
		$this->storage->acquireLock('key2', ILockingProvider::LOCK_SHARED, $lockingProvider);

		$this->assertTrue($this->storage->file_exists('key1'));
		$this->assertTrue($this->storage->file_exists('key2'));

		$this->instance->gc();

		$this->storage->releaseLock('key2', ILockingProvider::LOCK_SHARED, $lockingProvider);

		$this->assertFalse($this->storage->file_exists('key1'));
		$this->assertFalse($this->storage->file_exists('key2'));

	}
}
