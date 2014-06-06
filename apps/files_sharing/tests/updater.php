<?php
/**
 * ownCloud
 *
 * @author Morris Jobke
 * @copyright 2014 Morris Jobke <morris.jobke@gmail.com>
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

require_once __DIR__ . '/../appinfo/update.php';
require_once __DIR__ . '/base.php';

/**
 * Class Test_Files_Sharing_Updater
 */
class Test_Files_Sharing_Updater extends Test_Files_Sharing_Base {

	const TEST_FOLDER_NAME = '/folder_share_api_test';

	function setUp() {
		parent::setUp();

		$this->folder = self::TEST_FOLDER_NAME;

		$this->filename = '/share-api-test.txt';

		// save file with content
		$this->view->file_put_contents($this->filename, $this->data);
		$this->view->mkdir($this->folder);
		$this->view->file_put_contents($this->folder . '/' . $this->filename, $this->data);
	}

	function tearDown() {
		$this->view->unlink($this->filename);
		$this->view->deleteAll($this->folder);

		parent::tearDown();
	}

	/**
	 * test deletion of a folder which contains share mount points. Share mount
	 * points should move up to the parent before the folder gets deleted so
	 * that the mount point doesn't end up at the trash bin
	 */
	function testDeleteParentFolder() {
		$status = \OC_App::isEnabled('files_trashbin');
		\OC_App::enable('files_trashbin');

		\OCA\Files_Trashbin\Trashbin::registerHooks();
		OC_FileProxy::register(new OCA\Files\Share\Proxy());

		$fileinfo = \OC\Files\Filesystem::getFileInfo($this->folder);
		$this->assertTrue($fileinfo instanceof \OC\Files\FileInfo);

		\OCP\Share::shareItem('folder', $fileinfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_FILES_SHARING_API_USER2, 31);

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);
		$view = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2 . '/files');

		// check if user2 can see the shared folder
		$this->assertTrue($view->file_exists($this->folder));

		$view->mkdir("localFolder");
		$view->file_put_contents("localFolder/localFile.txt", "local file");

		$view->rename($this->folder, 'localFolder/' . $this->folder);

		// share mount point should now be moved to the subfolder
		$this->assertFalse($view->file_exists($this->folder));
		$this->assertTrue($view->file_exists('localFolder/' .$this->folder));

		$view->unlink('localFolder');

		$this->loginHelper(self::TEST_FILES_SHARING_API_USER2);

		// mount point should move up again
		$this->assertTrue($view->file_exists($this->folder));

		// trashbin should contain the local file but not the mount point
		$rootView = new \OC\Files\View('/' . self::TEST_FILES_SHARING_API_USER2);
		$dirContent = $rootView->getDirectoryContent('files_trashbin/files');
		$this->assertSame(1, count($dirContent));
		$firstElement = reset($dirContent);
		$ext = pathinfo($firstElement['path'], PATHINFO_EXTENSION);
		$this->assertTrue($rootView->file_exists('files_trashbin/files/localFolder.' . $ext . '/localFile.txt'));
		$this->assertFalse($rootView->file_exists('files_trashbin/files/localFolder.' . $ext . '/' . $this->folder));

		//cleanup
		$rootView->deleteAll('files_trashin');

		if ($status === false) {
			\OC_App::disable('files_trashbin');
		}
	}

}
