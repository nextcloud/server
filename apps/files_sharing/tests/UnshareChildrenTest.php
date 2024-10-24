<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OCP\Constants;
use OCP\Share\IShare;
use OCP\Util;

/**
 * Class UnshareChildrenTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests
 */
class UnshareChildrenTest extends TestCase {
	protected $subsubfolder;

	public const TEST_FOLDER_NAME = '/folder_share_api_test';

	private static $tempStorage;

	protected function setUp(): void {
		parent::setUp();

		Util::connectHook('OC_Filesystem', 'post_delete', '\OCA\Files_Sharing\Hooks', 'unshareChildren');

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder = '/subfolder_share_api_test';
		$this->subsubfolder = '/subsubfolder_share_api_test';

		$this->filename = '/share-api-test';

		// save file with content
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder . $this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
	}

	protected function tearDown(): void {
		if ($this->view) {
			$this->view->deleteAll($this->folder);
		}

		self::$tempStorage = null;

		parent::tearDown();
	}

	/**
	 * @medium
	 */
	public function testUnshareChildren(): void {
		$fileInfo2 = Filesystem::getFileInfo($this->folder);

		$this->share(
			IShare::TYPE_USER,
			$this->folder,
			self::TEST_FILES_SHARING_API_USER1,
			self::TEST_FILES_SHARING_API_USER2,
			Constants::PERMISSION_ALL
		);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// one folder should be shared with the user
		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, IShare::TYPE_USER);
		$this->assertCount(1, $shares);

		// move shared folder to 'localDir'
		Filesystem::mkdir('localDir');
		$result = Filesystem::rename($this->folder, '/localDir/' . $this->folder);
		$this->assertTrue($result);

		Filesystem::unlink('localDir');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// after the parent directory was deleted the share should be unshared
		$shares = $this->shareManager->getSharedWith(self::TEST_FILES_SHARING_API_USER2, IShare::TYPE_USER);
		$this->assertEmpty($shares);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// the folder for the owner should still exists
		$this->assertTrue(Filesystem::file_exists($this->folder));
	}
}
