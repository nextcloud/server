<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
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

namespace OCA\Files_sharing\Tests;

use OC\Files\Filesystem;
use OC\Files\View;

/**
 * Class EtagPropagation
 *
 * @group DB
 *
 * @package OCA\Files_sharing\Tests
 */
class EtagPropagation extends PropagationTestCase {
	/**
	 * "user1" is the admin who shares a folder "sub1/sub2/folder" with "user2" and "user3"
	 * "user2" receives the folder and puts it in "sub1/sub2/folder"
	 * "user3" receives the folder and puts it in "sub1/sub2/folder"
	 * "user2" reshares the subdir "sub1/sub2/folder/inside" with "user4"
	 * "user4" puts the received "inside" folder into "sub1/sub2/inside" (this is to check if it propagates across multiple subfolders)
	 */
	protected function setUpShares() {
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4] = [];

		$this->rootView = new View('');
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$view1 = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
		$view1->mkdir('/sub1/sub2/folder/inside');
		$view1->mkdir('/directReshare');
		$view1->mkdir('/sub1/sub2/folder/other');
		$view1->mkdir('/sub1/sub2/folder/other');
		$view1->file_put_contents('/foo.txt', 'foobar');
		$view1->file_put_contents('/sub1/sub2/folder/file.txt', 'foobar');
		$view1->file_put_contents('/sub1/sub2/folder/inside/file.txt', 'foobar');
		$folderInfo = $view1->getFileInfo('/sub1/sub2/folder');
		$this->assertInstanceOf('\OC\Files\FileInfo', $folderInfo);
		$fileInfo = $view1->getFileInfo('/foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		\OCP\Share::shareItem('file', $fileInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 31);
		\OCP\Share::shareItem('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 31);
		\OCP\Share::shareItem('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER3, 31);
		$folderInfo = $view1->getFileInfo('/directReshare');
		$this->assertInstanceOf('\OC\Files\FileInfo', $folderInfo);
		\OCP\Share::shareItem('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 31);
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1][''] = $view1->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['sub1'] = $view1->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['sub1/sub2'] = $view1->getFileInfo('sub1/sub2')->getId();

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$view2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$view2->mkdir('/sub1/sub2');
		$view2->rename('/folder', '/sub1/sub2/folder');
		$insideInfo = $view2->getFileInfo('/sub1/sub2/folder/inside');
		$this->assertInstanceOf('\OC\Files\FileInfo', $insideInfo);
		\OCP\Share::shareItem('folder', $insideInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER4, 31);
		$folderInfo = $view2->getFileInfo('/directReshare');
		$this->assertInstanceOf('\OC\Files\FileInfo', $folderInfo);
		\OCP\Share::shareItem('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER4, 31);
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2][''] = $view2->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2]['sub1'] = $view2->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2]['sub1/sub2'] = $view2->getFileInfo('sub1/sub2')->getId();

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		$view3 = new View('/' . self::TEST_FILES_SHARING_API_USER3 . '/files');
		$view3->mkdir('/sub1/sub2');
		$view3->rename('/folder', '/sub1/sub2/folder');
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3][''] = $view3->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3]['sub1'] = $view3->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3]['sub1/sub2'] = $view3->getFileInfo('sub1/sub2')->getId();

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		$view4 = new View('/' . self::TEST_FILES_SHARING_API_USER4 . '/files');
		$view4->mkdir('/sub1/sub2');
		$view4->rename('/inside', '/sub1/sub2/inside');
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4][''] = $view4->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4]['sub1'] = $view4->getFileInfo('sub1')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4]['sub1/sub2'] = $view4->getFileInfo('sub1/sub2')->getId();

		foreach ($this->fileIds as $user => $ids) {
			$this->loginAsUser($user);
			foreach ($ids as $id) {
				$path = $this->rootView->getPath($id);
				$this->fileEtags[$id] = $this->rootView->getFileInfo($path)->getEtag();
			}
		}
	}

	public function testOwnerWritesToShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::file_put_contents('/sub1/sub2/folder/asd.txt', 'bar');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testOwnerWritesToSingleFileShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::file_put_contents('/foo.txt', 'bar');
		$t = (int)Filesystem::filemtime('/foo.txt') - 1;
		Filesystem::touch('/foo.txt', $t);
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4, self::TEST_FILES_SHARING_API_USER3]);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2]);

		$this->assertAllUnchanged();
	}

	public function testOwnerWritesToShareWithReshare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::file_put_contents('/sub1/sub2/folder/inside/bar.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameInShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		Filesystem::rename('/sub1/sub2/folder/file.txt', '/sub1/sub2/folder/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameInReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::rename('/sub1/sub2/folder/inside/file.txt', '/sub1/sub2/folder/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameIntoReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::rename('/sub1/sub2/folder/file.txt', '/sub1/sub2/folder/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerRenameOutOfReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::rename('/sub1/sub2/folder/inside/file.txt', '/sub1/sub2/folder/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerDeleteInShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::unlink('/sub1/sub2/folder/file.txt');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testOwnerDeleteInReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		Filesystem::unlink('/sub1/sub2/folder/inside/file.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testOwnerUnshares() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$folderInfo = $this->rootView->getFileInfo('/' . self::TEST_FILES_SHARING_API_USER1 . '/files/sub1/sub2/folder');
		$this->assertInstanceOf('\OC\Files\FileInfo', $folderInfo);
		$folderId = $folderInfo->getId();
		$this->assertTrue(
			\OCP\Share::unshare(
				'folder',
				$folderId,
				\OCP\Share::SHARE_TYPE_USER,
				self::TEST_FILES_SHARING_API_USER2
			)
		);
		$this->assertEtagsForFoldersChanged([
			// direct recipient affected
			self::TEST_FILES_SHARING_API_USER2,
			// reshare recipient affected
			self::TEST_FILES_SHARING_API_USER4,
		]);

		$this->assertAllUnchanged();
	}

	public function testRecipientUnsharesFromSelf() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(
			$this->rootView->unlink('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/sub1/sub2/folder')
		);
		$this->assertEtagsForFoldersChanged([
			// direct recipient affected
			self::TEST_FILES_SHARING_API_USER2,
			// reshare recipient affected
			self::TEST_FILES_SHARING_API_USER4,
		]);

		$this->assertAllUnchanged();
	}

	public function testRecipientWritesToShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/sub1/sub2/folder/asd.txt', 'bar');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testRecipientWritesToReshare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/sub1/sub2/folder/inside/asd.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientWritesToOtherRecipientsReshare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		Filesystem::file_put_contents('/sub1/sub2/folder/inside/asd.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientRenameInShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::rename('/sub1/sub2/folder/file.txt', '/sub1/sub2/folder/renamed.txt');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testRecipientRenameInReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::rename('/sub1/sub2/folder/inside/file.txt', '/sub1/sub2/folder/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientRenameResharedFolder() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::rename('/directReshare', '/sub1/directReshare');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER2]);

		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER2], 'sub1');

		$this->assertAllUnchanged();
	}

	public function testRecipientDeleteInShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::unlink('/sub1/sub2/folder/file.txt');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER4]);
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3]);

		$this->assertAllUnchanged();
	}

	public function testRecipientDeleteInReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::unlink('/sub1/sub2/folder/inside/file.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testReshareRecipientWritesToReshare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		Filesystem::file_put_contents('/sub1/sub2/inside/asd.txt', 'bar');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testReshareRecipientRenameInReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		Filesystem::rename('/sub1/sub2/inside/file.txt', '/sub1/sub2/inside/renamed.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testReshareRecipientDeleteInReShare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		Filesystem::unlink('/sub1/sub2/inside/file.txt');
		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientUploadInDirectReshare() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/directReshare/test.txt', 'sad');
		$this->assertEtagsNotChanged([self::TEST_FILES_SHARING_API_USER3]);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testEtagChangeOnPermissionsChange() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);

		$view = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
		$folderInfo = $view->getFileInfo('/sub1/sub2/folder');

		\OCP\Share::setPermissions('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 17);

		$this->assertEtagsForFoldersChanged([self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}
}
