<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Server;
use OCP\Share\IShare;

/**
 * Class EtagPropagationTest
 *
 *
 * @package OCA\Files_Sharing\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'SLOWDB')]
class EtagPropagationTest extends PropagationTestCase {

	/**
	 * Sets up a semi-complex sharing scenario with multiple users, folders, and reshares.
	 * 
	 * Structure Overview:
	 * - "user1" is the owner, creates folders and files, shares with "user2" and "user3"
	 * - "user2" and "user3" accept and organize their shares
	 * - "user2" reshares one folder with "user4"
	 * - "user4" places received folder at a nested location (this is to check if it propagates across multiple subfolders)
	 */
	protected function setUpShares() {
		// Tracking arrays for file IDs and etags
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4] = [];

		$rootFolder = Server::get(IRootFolder::class);
		$shareManager = Server::get(\OCP\Share\IManager::class);

		$this->rootView = new View('');

		//
		// --- USER 1: creates resources, and initiates shares ---
		//
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$view1 = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');

		// Create folder hierarchy and files
		$view1->mkdir('/sub1/sub2/folder/inside');
		$view1->mkdir('/directReshare');
		$view1->mkdir('/sub1/sub2/folder/other');
		$view1->file_put_contents('/foo.txt', 'foobar');
		$view1->file_put_contents('/sub1/sub2/folder/file.txt', 'foobar');
		$view1->file_put_contents('/sub1/sub2/folder/inside/file.txt', 'foobar');

		// Assert initial resources exist
		$this->assertInstanceOf('\OC\Files\FileInfo', $view1->getFileInfo('/sub1/sub2/folder'));
		$this->assertInstanceOf('\OC\Files\FileInfo', $view1->getFileInfo('/foo.txt'));
		$this->assertInstanceOf('\OC\Files\FileInfo', $view1->getFileInfo('/directReshare'));

		// Prepare shares
		$fooNode = $rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER1)->get('/foo.txt');

		$fooShareToUser2 = $shareManager->newShare();
		$fooShareToUser2->setNode($fooNode)
			->setShareType(IShare::TYPE_USER)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE);
		$fooShareToUser2 = $shareManager->createShare($fooShareToUser2);

		$folderNode = $rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER1)->get('/sub1/sub2/folder');

		$folderShareToUser2 = $shareManager->newShare();
		$folderShareToUser2->setNode($folderNode)
			->setShareType(IShare::TYPE_USER)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setPermissions(Constants::PERMISSION_ALL);
		$folderShareToUser2 = $shareManager->createShare($folderShareToUser2);

		$folderShareToUser3 = $shareManager->newShare();
		$folderShareToUser3->setNode($folderNode)
			->setShareType(IShare::TYPE_USER)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER3)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setPermissions(Constants::PERMISSION_ALL);
		$folderShareToUser3 = $shareManager->createShare($folderShareToUser3);

		$directReshareNode = $rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER1)->get('/directReshare');

		$directReshareToUser2 = $shareManager->newShare();
		$directReshareToUser2->setNode($directReshareNode)
			->setShareType(IShare::TYPE_USER)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER2)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER1)
			->setPermissions(Constants::PERMISSION_ALL);
		$directReshareToUser2 = $shareManager->createShare($directReshareToUser2);

		// Track file ids
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1][''] 			= $view1->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['sub1'] 		= $view1->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['sub1/sub2'] = $view1->getFileInfo('sub1/sub2')->getId();

		//
		// --- USER 2: accepts shares from user 1, organizes folders, and initiates reshares ---
		//
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);

		// Accept shares from user 1
		$this->shareManager->acceptShare($fooShareToUser2, self::TEST_FILES_SHARING_API_USER2);
		$this->shareManager->acceptShare($folderShareToUser2, self::TEST_FILES_SHARING_API_USER2);
		$this->shareManager->acceptShare($directReshareToUser2, self::TEST_FILES_SHARING_API_USER2);

		// Organize received folders
		$view2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$view2->mkdir('/sub1/sub2');
		$view2->rename('/folder', '/sub1/sub2/folder');

		//$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);

		$this->assertInstanceOf('\OC\Files\FileInfo', $view2->getFileInfo('/sub1/sub2/folder/inside'));
		$this->assertInstanceOf('\OC\Files\FileInfo', $view2->getFileInfo('/directReshare'));

		// Prepare reshares from user2 to user4
		$user2InsideNode = $rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER2)->get('/sub1/sub2/folder/inside');

		$insideShareToUser4 = $shareManager->newShare();
		$insideShareToUser4->setNode($user2InsideNode)
			->setShareType(IShare::TYPE_USER)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER4)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setPermissions(Constants::PERMISSION_ALL);
		$insideShareToUser4 = $shareManager->createShare($insideShareToUser4);

		$user2DirectReshareNode = $rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER2)->get('/directReshare');

		$directReshareFromUser2ToUser4 = $shareManager->newShare();
		$directReshareFromUser2ToUser4->setNode($user2DirectReshareNode)
			->setShareType(IShare::TYPE_USER)
			->setSharedWith(self::TEST_FILES_SHARING_API_USER4)
			->setSharedBy(self::TEST_FILES_SHARING_API_USER2)
			->setPermissions(Constants::PERMISSION_ALL);
		$directReshareFromUser2ToUser4 = $shareManager->createShare($directReshareFromUser2ToUser4);

		$this->fileIds[self::TEST_FILES_SHARING_API_USER2]['']			= $view2->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2]['sub1']		= $view2->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2]['sub1/sub2']	= $view2->getFileInfo('sub1/sub2')->getId();

		//
		// --- USER 3: accepts share from user 1, organizes folders ---
		//
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		$this->shareManager->acceptShare($folderShareToUser3, self::TEST_FILES_SHARING_API_USER3);

		$view3 = new View('/' . self::TEST_FILES_SHARING_API_USER3 . '/files');
		$view3->mkdir('/sub1/sub2');
		$view3->rename('/folder', '/sub1/sub2/folder');

		$this->fileIds[self::TEST_FILES_SHARING_API_USER3]['']			= $view3->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3]['sub1']		= $view3->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3]['sub1/sub2']	= $view3->getFileInfo('sub1/sub2')->getId();

		//
		// --- USER 4: accepts re-shares from user 2, organizes folders ---
		//
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		$this->shareManager->acceptShare($insideShareToUser4, self::TEST_FILES_SHARING_API_USER4);
		$this->shareManager->acceptShare($directReshareFromUser2ToUser4, self::TEST_FILES_SHARING_API_USER4);

		$view4 = new View('/' . self::TEST_FILES_SHARING_API_USER4 . '/files');
		$view4->mkdir('/sub1/sub2');
		$view4->rename('/inside', '/sub1/sub2/inside');

		$this->fileIds[self::TEST_FILES_SHARING_API_USER4]['']			= $view4->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4]['sub1']		= $view4->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4]['sub1/sub2']	= $view4->getFileInfo('sub1/sub2')->getId();

		//
		// --- Collect initial etags for all tracked IDs ---
		//
		foreach ($this->fileIds as $user => $ids) {
			$this->loginAsUser($user);
			foreach ($ids as $id) {
				$path = $this->rootView->getPath($id);
				$this->rootView->getDirectoryContent($path); // trigger mountpoints
				$this->fileEtags[$id] = $this->rootView->getFileInfo($path)->getEtag();
			}
		}
	}

	public function testOwnerWritesToShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::file_put_contents('/sub1/sub2/folder/asd.txt', 'bar');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testOwnerWritesToSingleFileShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::file_put_contents('/foo.txt', 'longer_bar');
		$t = (int)Filesystem::filemtime('/foo.txt') - 1;
		Filesystem::touch('/foo.txt', $t);
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4, self::TEST_FILES_SHARING_API_USER3]);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2]);

		$this->assertAllUnchanged();
	}

	public function testOwnerWritesToShareWithReshare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::file_put_contents('/sub1/sub2/folder/inside/bar.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameInShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		Filesystem::rename('/sub1/sub2/folder/file.txt', '/sub1/sub2/folder/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameInReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::rename('/sub1/sub2/folder/inside/file.txt', '/sub1/sub2/folder/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameIntoReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::rename('/sub1/sub2/folder/file.txt', '/sub1/sub2/folder/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameOutOfReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::rename('/sub1/sub2/folder/inside/file.txt', '/sub1/sub2/folder/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerDeleteInShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::unlink('/sub1/sub2/folder/file.txt');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testOwnerDeleteInReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::unlink('/sub1/sub2/folder/inside/file.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerUnshares(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$folderInfo = $this->rootView->getFileInfo('/' . self::TEST_FILES_SHARING_API_USER1 . '/files/sub1/sub2/folder');
		$this->assertInstanceOf('\OC\Files\FileInfo', $folderInfo);

		$node = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER1)->get('/sub1/sub2/folder');
		$shareManager = Server::get(\OCP\Share\IManager::class);
		$shares = $shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER1, IShare::TYPE_USER, $node, true);

		foreach ($shares as $share) {
			if ($share->getSharedWith() === self::TEST_FILES_SHARING_API_USER2) {
				$shareManager->deleteShare($share);
			}
		}

		$this->assertEtagsForFoldersChanged([
			// direct recipient affected
			self::TEST_FILES_SHARING_API_USER2,
		]);

		$this->assertAllUnchanged();
	}

	public function testOwnerUnsharesFlatReshares(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$folderInfo = $this->rootView->getFileInfo('/' . self::TEST_FILES_SHARING_API_USER1 . '/files/sub1/sub2/folder/inside');
		$this->assertInstanceOf('\OC\Files\FileInfo', $folderInfo);

		$node = \OC::$server->getUserFolder(self::TEST_FILES_SHARING_API_USER1)->get('/sub1/sub2/folder/inside');
		$shareManager = Server::get(\OCP\Share\IManager::class);
		$shares = $shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER1, IShare::TYPE_USER, $node, true);

		foreach ($shares as $share) {
			$shareManager->deleteShare($share);
		}

		$this->assertEtagsForFoldersChanged([
			// direct recipient affected
			self::TEST_FILES_SHARING_API_USER4,
		]);

		$this->assertAllUnchanged();
	}

	public function testRecipientUnsharesFromSelf(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$ls = $this->rootView->getDirectoryContent('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/sub1/sub2/');
		$this->assertTrue(
			$this->rootView->unlink('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/sub1/sub2/folder')
		);
		$this->assertEtagsForFoldersChanged([
			// direct recipient affected
			self::TEST_FILES_SHARING_API_USER2,
		]);

		$this->assertAllUnchanged();
	}

	public function testRecipientWritesToShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/sub1/sub2/folder/asd.txt', 'bar');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3
		]);

		$this->assertAllUnchanged();
	}

	public function testRecipientWritesToReshare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/sub1/sub2/folder/inside/asd.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientWritesToOtherRecipientsReshare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		Filesystem::file_put_contents('/sub1/sub2/folder/inside/asd.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientRenameInShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::rename('/sub1/sub2/folder/file.txt', '/sub1/sub2/folder/renamed.txt');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testRecipientRenameInReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::rename('/sub1/sub2/folder/inside/file.txt', '/sub1/sub2/folder/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientRenameResharedFolder(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::rename('/directReshare', '/sub1/directReshare');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER2]);

		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER2], 'sub1');

		$this->assertAllUnchanged();
	}

	public function testRecipientDeleteInShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::unlink('/sub1/sub2/folder/file.txt');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testRecipientDeleteInReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::unlink('/sub1/sub2/folder/inside/file.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testReshareRecipientWritesToReshare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		Filesystem::file_put_contents('/sub1/sub2/inside/asd.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testReshareRecipientRenameInReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		Filesystem::rename('/sub1/sub2/inside/file.txt', '/sub1/sub2/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testReshareRecipientDeleteInReShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		Filesystem::unlink('/sub1/sub2/inside/file.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientUploadInDirectReshare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/directReshare/test.txt', 'sad');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER3]);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testEtagChangeOnPermissionsChange(): void {
		$userFolder = $this->rootFolder->getUserFolder(self::TEST_FILES_SHARING_API_USER1);
		$node = $userFolder->get('/sub1/sub2/folder');

		$shares = $this->shareManager->getSharesBy(self::TEST_FILES_SHARING_API_USER1, IShare::TYPE_USER, $node);
		/** @var IShare[] $shares */
		$shares = array_filter($shares, function (IShare $share) {
			return $share->getSharedWith() === self::TEST_FILES_SHARING_API_USER2;
		});
		$this->assertCount(1, $shares);

		$share = $shares[0];
		$share->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_SHARE);
		$this->shareManager->updateShare($share);

		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER2]);

		$this->assertAllUnchanged();
	}
}
