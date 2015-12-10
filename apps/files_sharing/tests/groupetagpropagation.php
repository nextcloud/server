<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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
 * @group DB
 *
 * @package OCA\Files_sharing\Tests
 */
class GroupEtagPropagation extends PropagationTestCase {
	/**
	 * "user1" creates /test, /test/sub and shares with group1
	 * "user2" (in group1) reshares /test with group2 and reshared /test/sub with group3
	 * "user3" (in group 2)
	 * "user4" (in group 3)
	 */
	protected function setUpShares() {
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3] = [];
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4] = [];

		$this->rootView = new View('');
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER1);
		$view1 = new View('/' . self::TEST_FILES_SHARING_API_USER1 . '/files');
		$view1->mkdir('/test/sub');
		$folderInfo = $view1->getFileInfo('/test');
		\OCP\Share::shareItem('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_GROUP, 'group1', 31);
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1][''] = $view1->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['test'] = $view1->getFileInfo('test')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['test/sub'] = $view1->getFileInfo('test/sub')->getId();

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$view2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');
		$folderInfo = $view2->getFileInfo('/test');
		$subFolderInfo = $view2->getFileInfo('/test/sub');
		\OCP\Share::shareItem('folder', $folderInfo->getId(), \OCP\Share::SHARE_TYPE_GROUP, 'group2', 31);
		\OCP\Share::shareItem('folder', $subFolderInfo->getId(), \OCP\Share::SHARE_TYPE_GROUP, 'group3', 31);
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2][''] = $view2->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2]['test'] = $view2->getFileInfo('test')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER2]['test/sub'] = $view2->getFileInfo('test/sub')->getId();

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);
		$view3 = new View('/' . self::TEST_FILES_SHARING_API_USER3 . '/files');
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3][''] = $view3->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3]['test'] = $view3->getFileInfo('test')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER3]['test/sub'] = $view3->getFileInfo('test/sub')->getId();

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);
		$view4 = new View('/' . self::TEST_FILES_SHARING_API_USER4 . '/files');
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4][''] = $view4->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER4]['sub'] = $view4->getFileInfo('sub')->getId();

		foreach ($this->fileIds as $user => $ids) {
			$this->loginAsUser($user);
			foreach ($ids as $id) {
				$path = $this->rootView->getPath($id);
				$this->fileEtags[$id] = $this->rootView->getFileInfo($path)->getEtag();
			}
		}
	}

	public function testGroupReShareRecipientWrites() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);

		Filesystem::file_put_contents('/test/sub/file.txt', 'asd');

		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testGroupReShareSubFolderRecipientWrites() {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);

		Filesystem::file_put_contents('/sub/file.txt', 'asd');

		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}
}
