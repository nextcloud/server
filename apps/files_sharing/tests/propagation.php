<?php
/**
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

class Propagation extends TestCase {
	public function testSizePropagationWhenOwnerChangesFile() {
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$recipientView = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$ownerView = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$ownerView->mkdir('/sharedfolder/subfolder');
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'bar');

		$sharedFolderInfo = $ownerView->getFileInfo('/sharedfolder', false);
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		$ownerRootInfo = $ownerView->getFileInfo('', false);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($recipientView->file_exists('/sharedfolder/subfolder/foo.txt'));
		$recipientRootInfo = $recipientView->getFileInfo('', false);

		// when file changed as owner
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		// size of recipient's root stays the same
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$newRecipientRootInfo = $recipientView->getFileInfo('', false);
		$this->assertEquals($recipientRootInfo->getSize(), $newRecipientRootInfo->getSize());

		// size of owner's root increases
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$newOwnerRootInfo = $ownerView->getFileInfo('', false);
		$this->assertEquals($ownerRootInfo->getSize() + 3, $newOwnerRootInfo->getSize());
	}

	public function testSizePropagationWhenRecipientChangesFile() {
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$recipientView = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$ownerView = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$ownerView->mkdir('/sharedfolder/subfolder');
		$ownerView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'bar');

		$sharedFolderInfo = $ownerView->getFileInfo('/sharedfolder', false);
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		$ownerRootInfo = $ownerView->getFileInfo('', false);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($recipientView->file_exists('/sharedfolder/subfolder/foo.txt'));
		$recipientRootInfo = $recipientView->getFileInfo('', false);

		// when file changed as recipient
		$recipientView->file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		// size of recipient's root stays the same
		$newRecipientRootInfo = $recipientView->getFileInfo('', false);
		$this->assertEquals($recipientRootInfo->getSize(), $newRecipientRootInfo->getSize());

		// size of owner's root increases
		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$newOwnerRootInfo = $ownerView->getFileInfo('', false);
		$this->assertEquals($ownerRootInfo->getSize() + 3, $newOwnerRootInfo->getSize());
	}

	/**
	 * @return \OC\Files\View[]
	 */
	private function setupViews() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$view1 = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$view2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$view2->mkdir('/sharedfolder/subfolder');
		$view2->file_put_contents('/sharedfolder/subfolder/foo.txt', 'bar');
		return [$view1, $view2];
	}

	public function testEtagPropagationSingleUserShareRecipient() {
		/**
		 * @var \OC\Files\View $view1
		 * @var \OC\Files\View $view2
		 */
		list($view1, $view2) = $this->setupViews();

		$sharedFolderInfo = $view2->getFileInfo('/sharedfolder');
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($view1->file_exists('/sharedfolder/subfolder/foo.txt'));

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$rootInfo = $view2->getFileInfo('');
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);

		Filesystem::file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$newRootInfo = $view2->getFileInfo('');
		$this->assertNotEquals($rootInfo->getEtag(), $newRootInfo->getEtag());
	}

	public function testEtagPropagationSingleUserShare() {
		/**
		 * @var \OC\Files\View $view1
		 * @var \OC\Files\View $view2
		 */
		list($view1, $view2) = $this->setupViews();

		$sharedFolderInfo = $view2->getFileInfo('/sharedfolder');
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($view1->file_exists('/sharedfolder/subfolder/foo.txt'));

		$rootInfo = $view1->getFileInfo('');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$newRootInfo = $view1->getFileInfo('');
		$this->assertNotEquals($rootInfo->getEtag(), $newRootInfo->getEtag());
	}

	public function testEtagPropagationGroupShare() {
		/**
		 * @var \OC\Files\View $view1
		 * @var \OC\Files\View $view2
		 */
		list($view1, $view2) = $this->setupViews();

		$sharedFolderInfo = $view2->getFileInfo('/sharedfolder');
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_GROUP, 'group', 31);
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$this->assertTrue($view1->file_exists('/sharedfolder/subfolder/foo.txt'));

		$rootInfo = $view1->getFileInfo('');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		Filesystem::file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$newRootInfo = $view1->getFileInfo('');
		$this->assertNotEquals($rootInfo->getEtag(), $newRootInfo->getEtag());
	}

	public function testEtagPropagationGroupShareOtherRecipient() {
		/**
		 * @var \OC\Files\View $view1
		 * @var \OC\Files\View $view2
		 */
		list($view1, $view2) = $this->setupViews();

		$sharedFolderInfo = $view2->getFileInfo('/sharedfolder');
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_GROUP, 'group', 31);
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		$view3 = new View('/' . self::TEST_FILES_SHARING_API_USER3 . '/files');
		$this->assertTrue($view3->file_exists('/sharedfolder/subfolder/foo.txt'));

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$rootInfo = $view1->getFileInfo('');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		Filesystem::file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$newRootInfo = $view1->getFileInfo('');
		$this->assertNotEquals($rootInfo->getEtag(), $newRootInfo->getEtag());
	}

	public function testEtagPropagationOtherShare() {
		/**
		 * @var \OC\Files\View $view1
		 * @var \OC\Files\View $view2
		 */
		list($view1, $view2) = $this->setupViews();

		$sharedFolderInfo = $view2->getFileInfo('/sharedfolder');
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER3, 31);
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		$view3 = new View('/' . self::TEST_FILES_SHARING_API_USER3 . '/files');
		$this->assertTrue($view3->file_exists('/sharedfolder/subfolder/foo.txt'));

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$rootInfo = $view1->getFileInfo('');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		Filesystem::file_put_contents('/sharedfolder/subfolder/foo.txt', 'foobar');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$newRootInfo = $view1->getFileInfo('');
		$this->assertNotEquals($rootInfo->getEtag(), $newRootInfo->getEtag());
	}

	public function testEtagPropagationOtherShareSubFolder() {
		/**
		 * @var \OC\Files\View $view1
		 * @var \OC\Files\View $view2
		 */
		list($view1, $view2) = $this->setupViews();

		$sharedFolderInfo = $view2->getFileInfo('/sharedfolder');
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER1, 31);
		$sharedFolderInfo = $view2->getFileInfo('/sharedfolder/subfolder');
		\OCP\Share::shareItem('folder', $sharedFolderInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER3, 31);
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		$view3 = new View('/' . self::TEST_FILES_SHARING_API_USER3 . '/files');
		$this->assertTrue($view3->file_exists('/subfolder/foo.txt'));

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$rootInfo = $view1->getFileInfo('');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		Filesystem::file_put_contents('/subfolder/foo.txt', 'foobar');

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$newRootInfo = $view1->getFileInfo('');
		$this->assertNotEquals($rootInfo->getEtag(), $newRootInfo->getEtag());
	}
}
