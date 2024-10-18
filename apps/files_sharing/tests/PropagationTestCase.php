<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\View;
use OCA\Files_Sharing\Helper;

abstract class PropagationTestCase extends TestCase {
	/**
	 * @var View
	 */
	protected $rootView;
	protected $fileIds = []; // [$user=>[$path=>$id]]
	protected $fileEtags = []; // [$id=>$etag]

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		Helper::registerHooks();
	}

	protected function setUp(): void {
		parent::setUp();
		$this->setUpShares();
	}

	protected function tearDown(): void {
		\OC_Hook::clear('OC_Filesystem', 'post_write');
		\OC_Hook::clear('OC_Filesystem', 'post_delete');
		\OC_Hook::clear('OC_Filesystem', 'post_rename');
		\OC_Hook::clear('OCP\Share', 'post_update_permissions');
		parent::tearDown();
	}

	abstract protected function setUpShares();

	/**
	 * @param string[] $users
	 * @param string $subPath
	 */
	protected function assertEtagsChanged($users, $subPath = '') {
		$oldUser = \OC::$server->getUserSession()->getUser();
		foreach ($users as $user) {
			$this->loginAsUser($user);
			$id = $this->fileIds[$user][$subPath];
			$path = $this->rootView->getPath($id);
			$etag = $this->rootView->getFileInfo($path)->getEtag();
			$this->assertNotEquals($this->fileEtags[$id], $etag, 'Failed asserting that the etag for "' . $subPath . '" of user ' . $user . ' has changed');
			$this->fileEtags[$id] = $etag;
		}
		$this->loginAsUser($oldUser->getUID());
	}

	/**
	 * @param string[] $users
	 * @param string $subPath
	 */
	protected function assertEtagsNotChanged($users, $subPath = '') {
		$oldUser = \OC::$server->getUserSession()->getUser();
		foreach ($users as $user) {
			$this->loginAsUser($user);
			$id = $this->fileIds[$user][$subPath];
			$path = $this->rootView->getPath($id);
			$etag = $this->rootView->getFileInfo($path)->getEtag();
			$this->assertEquals($this->fileEtags[$id], $etag, 'Failed asserting that the etag for "' . $subPath . '" of user ' . $user . ' has not changed');
			$this->fileEtags[$id] = $etag;
		}
		$this->loginAsUser($oldUser->getUID());
	}

	/**
	 * Assert that the etags for the root, /sub1 and /sub1/sub2 have changed
	 *
	 * @param string[] $users
	 */
	protected function assertEtagsForFoldersChanged($users) {
		$this->assertEtagsChanged($users);

		$this->assertEtagsChanged($users, 'sub1');
		$this->assertEtagsChanged($users, 'sub1/sub2');
	}

	protected function assertAllUnchanged() {
		$users = [self::TEST_FILES_SHARING_API_USER1, self::TEST_FILES_SHARING_API_USER2,
			self::TEST_FILES_SHARING_API_USER3, self::TEST_FILES_SHARING_API_USER4];
		$this->assertEtagsNotChanged($users);
	}
}
