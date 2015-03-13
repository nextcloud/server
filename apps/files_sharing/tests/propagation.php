<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2015 Robin Appelman <icewind@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use OC\Files\View;

require_once __DIR__ . '/base.php';

class Test_Files_Sharing_Propagation extends Test_Files_Sharing_Base {

	public function testSizePropagationWhenOwnerChangesFile() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$recipientView = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$ownerView = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$ownerView->mkdir('/sharedfolder/subfolder');
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'bar');

		$sharedFolderInfo = $ownerView->getFileInfo('/sharedfolder', false);
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		$ownerRootInfo = $ownerView->getFileInfo('', false);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($recipientView->file_exists('/sharedfolder/subfolder/foo.txt'));
		$recipientRootInfo = $recipientView->getFileInfo('', false);

		// when file changed as owner
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		// size of recipient's root stays the same
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$newRecipientRootInfo = $recipientView->getFileInfo('', false);
		$this->assertEquals($recipientRootInfo->getSize(), $newRecipientRootInfo->getSize());

		// size of owner's root increases
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$newOwnerRootInfo = $ownerView->getFileInfo('', false);
		$this->assertEquals($ownerRootInfo->getSize() + 3, $newOwnerRootInfo->getSize());
	}

	public function testSizePropagationWhenRecipientChangesFile() {
		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$recipientView = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$ownerView = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$ownerView->mkdir('/sharedfolder/subfolder');
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'bar');

		$sharedFolderInfo = $ownerView->getFileInfo('/sharedfolder', false);
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		$ownerRootInfo = $ownerView->getFileInfo('', false);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($recipientView->file_exists('/sharedfolder/subfolder/foo.txt'));
		$recipientRootInfo = $recipientView->getFileInfo('', false);

		// when file changed as recipient
		$recipientView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		// size of recipient's root stays the same
		$newRecipientRootInfo = $recipientView->getFileInfo('', false);
		$this->assertEquals($recipientRootInfo->getSize(), $newRecipientRootInfo->getSize());

		// size of owner's root increases
		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$newOwnerRootInfo = $ownerView->getFileInfo('', false);
		$this->assertEquals($ownerRootInfo->getSize() + 3, $newOwnerRootInfo->getSize());
	}
}
