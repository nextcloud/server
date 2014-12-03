<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
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


use OCA\Files\Share;

/**
 * Class Test_Files_Sharing_Proxy
 */
class Test_Files_Sharing_Proxy extends OCA\Files_sharing\Tests\TestCase {

	const TEST_FOLDER_NAME = '/folder_share_api_test';

	private static $tempStorage;

	protected function setUp() {
		parent::setUp();

		// load proxies
		OC::$CLASSPATH['OCA\Files\Share\Proxy'] = 'files_sharing/lib/proxy.php';
		OC_FileProxy::register(new OCA\Files\Share\Proxy());

		$this->folder = self::TEST_FOLDER_NAME;
		$this->subfolder  = '/subfolder_share_api_test';
		$this->subsubfolder = '/subsubfolder_share_api_test';

		$this->filename = '/share-api-test';

		// save file with content
		$this->view->mkdir($this->folder);
		$this->view->mkdir($this->folder . $this->subfolder);
		$this->view->mkdir($this->folder . $this->subfolder . $this->subsubfolder);
		$this->view->file_put_contents($this->folder.$this->filename, $this->data);
		$this->view->file_put_contents($this->folder . $this->subfolder . $this->filename, $this->data);
	}

	protected function tearDown() {
		$this->view->deleteAll($this->folder);

		self::$tempStorage = null;

		parent::tearDown();
	}

	/**
	 * @medium
	 */
	function testpreUnlink() {

		$fileInfo2 = \OC\Files\Filesystem::getFileInfo($this->folder);

		$result = \OCP\Share::shareItem('folder', $fileInfo2->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 31);
		$this->assertTrue($result);

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// one folder should be shared with the user
		$sharedFolders = \OCP\Share::getItemsSharedWith('folder');
		$this->assertSame(1, count($sharedFolders));

		// move shared folder to 'localDir'
		\OC\Files\Filesystem::mkdir('localDir');
		$result = \OC\Files\Filesystem::rename($this->folder, '/localDir/' . $this->folder);
		$this->assertTrue($result);

		\OC\Files\Filesystem::unlink('localDir');

		self::loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// after the parent directory was deleted the share should be unshared
		$sharedFolders = \OCP\Share::getItemsSharedWith('folder');
		$this->assertTrue(empty($sharedFolders));

		self::loginHelper(self::TEST_FILES_SHARING_API_USER1);

		// the folder for the owner should still exists
		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->folder));
	}
}
