<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Cache;

use OC\Cache\File;
use OC\Files\Filesystem;
use OC\Files\Storage\Local;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCP\Files\LockNotAcquiredException;
use OCP\Files\Mount\IMountManager;
use OCP\ITempManager;
use OCP\Lock\LockedException;
use OCP\Server;
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
	/**
	 * @var \OC\Files\View
	 * */
	private $rootView;

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
		$manager = Server::get(IMountManager::class);
		$manager->removeMount('/test');

		$storage = new Temporary([]);
		Filesystem::mount($storage, [], '/test/cache');

		//set up the users dir
		$this->rootView = new View('');
		$this->rootView->mkdir('/test');

		$this->instance = new File();

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

	private function setupMockStorage() {
		$mockStorage = $this->getMockBuilder(Local::class)
			->onlyMethods(['filemtime', 'unlink'])
			->setConstructorArgs([['datadir' => Server::get(ITempManager::class)->getTemporaryFolder()]])
			->getMock();

		Filesystem::mount($mockStorage, [], '/test/cache');

		return $mockStorage;
	}

	public function testGarbageCollectOldKeys(): void {
		$mockStorage = $this->setupMockStorage();

		$mockStorage->expects($this->atLeastOnce())
			->method('filemtime')
			->willReturn(100);
		$mockStorage->expects($this->once())
			->method('unlink')
			->with('key1')
			->willReturn(true);

		$this->instance->set('key1', 'value1');
		$this->instance->gc();
	}

	public function testGarbageCollectLeaveRecentKeys(): void {
		$mockStorage = $this->setupMockStorage();

		$mockStorage->expects($this->atLeastOnce())
			->method('filemtime')
			->willReturn(time() + 3600);
		$mockStorage->expects($this->never())
			->method('unlink')
			->with('key1');
		$this->instance->set('key1', 'value1');
		$this->instance->gc();
	}

	public static function lockExceptionProvider(): array {
		return [
			[new LockedException('key1')],
			[new LockNotAcquiredException('key1', 1)],
		];
	}

	/**
	 * @dataProvider lockExceptionProvider
	 */
	public function testGarbageCollectIgnoreLockedKeys($testException): void {
		$mockStorage = $this->setupMockStorage();

		$mockStorage->expects($this->atLeastOnce())
			->method('filemtime')
			->willReturn(100);
		$mockStorage->expects($this->atLeastOnce())
			->method('unlink')->willReturnOnConsecutiveCalls($this->throwException($testException), $this->returnValue(true));

		$this->instance->set('key1', 'value1');
		$this->instance->set('key2', 'value2');

		$this->instance->gc();
	}
}
