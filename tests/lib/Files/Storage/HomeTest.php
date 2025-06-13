<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\Home;
use OC\User\User;
use OCP\Files;
use OCP\ITempManager;
use OCP\Server;

class DummyUser extends User {
	/**
	 * @param string $uid
	 * @param string $home
	 */
	public function __construct(
		private $uid,
		private $home,
	) {
	}

	public function getHome() {
		return $this->home;
	}

	public function getUID() {
		return $this->uid;
	}
}

/**
 * Class Home
 *
 * @group DB
 *
 * @package Test\Files\Storage
 */
class HomeTest extends Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	private $userId;

	/**
	 * @var \OC\User\User $user
	 */
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->userId = $this->getUniqueID('user_');
		$this->user = new DummyUser($this->userId, $this->tmpDir);
		$this->instance = new Home(['user' => $this->user]);
	}

	protected function tearDown(): void {
		Files::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	/**
	 * Tests that the home id is in the format home::user1
	 */
	public function testId(): void {
		$this->assertEquals('home::' . $this->userId, $this->instance->getId());
	}

	/**
	 * Tests that getCache() returns an instance of HomeCache
	 */
	public function testGetCacheReturnsHomeCache(): void {
		$this->assertInstanceOf('\OC\Files\Cache\HomeCache', $this->instance->getCache());
	}

	public function testGetOwner(): void {
		$this->assertEquals($this->userId, $this->instance->getOwner(''));
	}
}
