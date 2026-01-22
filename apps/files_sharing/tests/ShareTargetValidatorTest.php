<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\ShareTargetValidator;
use OCP\Constants;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Folder;
use OCP\IUser;
use OCP\Server;
use OCP\Share\IShare;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class ShareTargetValidatorTest extends TestCase {
	private ShareTargetValidator $targetValidator;

	private IUser $user2;
	protected string $folder2;

	protected function setUp(): void {
		parent::setUp();

		$this->folder = '/folder_share_storage_test';
		$this->folder2 = '/folder_share_storage_test2';

		$this->filename = '/share-api-storage.txt';


		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder2);

		// save file with content
		$this->view->file_put_contents($this->filename, 'root file');
		$this->view->file_put_contents($this->folder . $this->filename, 'file in subfolder');
		$this->view->file_put_contents($this->folder2 . $this->filename, 'file in subfolder2');

		$this->targetValidator = Server::get(ShareTargetValidator::class);
		$this->user2 = $this->createMock(IUser::class);
		$this->user2->method('getUID')
			->willReturn(self::TEST_FILES_SHARING_API_USER2);
	}


	/**
	 * test if the mount point moves up if the parent folder no longer exists
	 */
	public function testShareMountLoseParentFolder(): void {
		// share to user
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share->setTarget('/foo/bar' . $this->folder);
		$this->shareManager->moveShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertSame('/foo/bar' . $this->folder, $share->getTarget());

		$this->targetValidator->verifyMountPoint($this->user2, $share, [], [$share]);

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertSame($this->folder, $share->getTarget());

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);
		$this->view->unlink($this->folder);
	}

	/**
	 * test if the mount point gets renamed if a folder exists at the target
	 */
	public function testShareMountOverFolder(): void {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$this->view2->mkdir('bar');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// share to user
		$share = $this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share->setTarget('/bar');
		$this->shareManager->moveShare($share, self::TEST_FILES_SHARING_API_USER2);

		$share = $this->shareManager->getShareById($share->getFullId());

		$this->targetValidator->verifyMountPoint($this->user2, $share, [], [$share]);

		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertSame('/bar (2)', $share->getTarget());

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share);
		$this->view->unlink($this->folder);
	}

	/**
	 * test if the mount point gets renamed if another share exists at the target
	 */
	public function testShareMountOverShare(): void {
		// share to user
		$share2 = $this->share(
			IShare::TYPE_USER,
			$this->folder2,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL);
		$this->shareManager->acceptShare($share2, self::TEST_FILES_SHARING_API_USER2);

		$conflictingMount = $this->createMock(ICachedMountInfo::class);
		$this->targetValidator->verifyMountPoint($this->user2, $share2, [
			'/' . $this->user2->getUID() . '/files' . $this->folder2 . '/' => $conflictingMount
		], [$share2]);

		$share2 = $this->shareManager->getShareById($share2->getFullId());

		$this->assertSame("{$this->folder2} (2)", $share2->getTarget());

		//cleanup
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->shareManager->deleteShare($share2);
		$this->view->unlink($this->folder);
	}
}
