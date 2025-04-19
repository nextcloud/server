<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Cache;

use OCP\Files\Mount\IMountManager;
use OCP\Lock\ILockingProvider;
use Test\Traits\UserTrait;

/**
 * Class FileCacheTest
 *
 * @group DB
 *
 * @package Test\Cache
 */
class FileCacheTest extends TestCache {
	use UserTrait;

	/**
	 * @var string
	 * */
	private $user;
	/**
	 * @var string
	 * */
	private $datadir;
	/**
	 * @var \OC\Files\Storage\Storage
	 * */
	private $storage;

	public function skip() {
		//$this->skipUnless(OC_User::isLoggedIn());
	}

	protected function setUp(): void {
		parent::setUp();

		//login
		$this->createUser('test', 'test');

		$this->user = \OC_User::getUser();
		\OC_User::setUserId('test');

		//clear all proxies and hooks so we can do clean testing
		\OC_Hook::clear('OC_Filesystem');

		/** @var IMountManager $manager */
		$manager = \OC::$server->get(IMountManager::class);
		$manager->removeMount('/test');

		$this->storage = new \OC\Files\Storage\Temporary([]);
		\OC\Files\Filesystem::mount($this->storage, [], '/test/cache');

		$this->instance = new \OC\Cache\File();

		// forces creation of cache folder for subsequent tests
		$this->instance->set('hack', 'hack');
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->remove('hack', 'hack');
		}

		\OC_User::setUserId($this->user);

		if ($this->instance) {
			$this->instance->clear();
			$this->instance = null;
		}

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
