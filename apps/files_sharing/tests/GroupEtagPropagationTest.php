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
use OCP\Share\IShare;

/**
 * @package OCA\Files_Sharing\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'SLOWDB')]
class GroupEtagPropagationTest extends PropagationTestCase {
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

		$share = $this->share(
			IShare::TYPE_GROUP,
			'/test',
			self::TEST_FILES_SHARING_API_USER1,
			'group1',
			Constants::PERMISSION_ALL
		);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER2);
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1][''] = $view1->getFileInfo('')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['test'] = $view1->getFileInfo('test')->getId();
		$this->fileIds[self::TEST_FILES_SHARING_API_USER1]['test/sub'] = $view1->getFileInfo('test/sub')->getId();

		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$view2 = new View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		$share = $this->share(
			IShare::TYPE_GROUP,
			'/test',
			self::TEST_FILES_SHARING_API_USER2,
			'group2',
			Constants::PERMISSION_ALL
		);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER3);
		$share = $this->share(
			IShare::TYPE_GROUP,
			'/test/sub',
			self::TEST_FILES_SHARING_API_USER2,
			'group3',
			Constants::PERMISSION_ALL
		);
		$this->shareManager->acceptShare($share, self::TEST_FILES_SHARING_API_USER4);

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

	public function testGroupReShareRecipientWrites(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER3);

		Filesystem::file_put_contents('/test/sub/file.txt', 'asd');

		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testGroupReShareSubFolderRecipientWrites(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER4);

		Filesystem::file_put_contents('/sub/file.txt', 'asd');

		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2, self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4]);

		$this->assertAllUnchanged();
	}

	public function testRecipientUnsharesFromSelf(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		$this->assertTrue(
			$this->rootView->unlink('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/test')
		);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER2]);

		$this->assertAllUnchanged();
	}

	public function testRecipientUnsharesFromSelfUniqueGroupShare(): void {
		$this->loginAsUser(self::TEST_FILES_SHARING_API_USER2);
		// rename to create an extra entry in the share table
		$this->rootView->rename('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/test', '/' . self::TEST_FILES_SHARING_API_USER2 . '/files/test_renamed');
		$this->assertTrue(
			$this->rootView->unlink('/' . self::TEST_FILES_SHARING_API_USER2 . '/files/test_renamed')
		);
		$this->assertEtagsChanged([self::TEST_FILES_SHARING_API_USER2]);

		$this->assertAllUnchanged();
	}
}
