<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Constants;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Class LockingTest
 *
 *
 * @package OCA\Files_Sharing\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class LockingTest extends TestCase {
	/**
	 * @var \Test\Util\User\Dummy
	 */
	private $userBackend;

	private $ownerUid;
	private $recipientUid;

	protected function setUp(): void {
		parent::setUp();

		$this->userBackend = new \Test\Util\User\Dummy();
		Server::get(IUserManager::class)->registerBackend($this->userBackend);

		$this->ownerUid = $this->getUniqueID('owner_');
		$this->recipientUid = $this->getUniqueID('recipient_');
		$this->userBackend->createUser($this->ownerUid, '');
		$this->userBackend->createUser($this->recipientUid, '');

		$this->loginAsUser($this->ownerUid);
		Filesystem::mkdir('/foo');
		Filesystem::file_put_contents('/foo/bar.txt', 'asd');
		$fileId = Filesystem::getFileInfo('/foo/bar.txt')->getId();

		$this->share(
			IShare::TYPE_USER,
			'/foo/bar.txt',
			$this->ownerUid,
			$this->recipientUid,
			Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE
		);

		$this->loginAsUser($this->recipientUid);
		$this->assertTrue(Filesystem::file_exists('bar.txt'));
	}

	protected function tearDown(): void {
		Server::get(IUserManager::class)->removeBackend($this->userBackend);
		parent::tearDown();
	}


	public function testLockAsRecipient(): void {
		$this->expectException(LockedException::class);

		$this->loginAsUser($this->ownerUid);

		Filesystem::initMountPoints($this->recipientUid);
		$recipientView = new View('/' . $this->recipientUid . '/files');
		$recipientView->lockFile('bar.txt', ILockingProvider::LOCK_EXCLUSIVE);

		Filesystem::rename('/foo', '/asd');
	}

	public function testUnLockAsRecipient(): void {
		$this->loginAsUser($this->ownerUid);

		Filesystem::initMountPoints($this->recipientUid);
		$recipientView = new View('/' . $this->recipientUid . '/files');
		$recipientView->lockFile('bar.txt', ILockingProvider::LOCK_EXCLUSIVE);
		$recipientView->unlockFile('bar.txt', ILockingProvider::LOCK_EXCLUSIVE);

		$this->assertTrue(Filesystem::rename('/foo', '/asd'));
	}

	public function testChangeLock(): void {
		Filesystem::initMountPoints($this->recipientUid);
		$recipientView = new View('/' . $this->recipientUid . '/files');
		$recipientView->lockFile('bar.txt', ILockingProvider::LOCK_SHARED);
		$recipientView->changeLock('bar.txt', ILockingProvider::LOCK_EXCLUSIVE);
		$recipientView->unlockFile('bar.txt', ILockingProvider::LOCK_EXCLUSIVE);

		$this->addToAssertionCount(1);
	}
}
