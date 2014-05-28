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

		$removeShares = \OC_DB::prepare('DELETE FROM `*PREFIX*share`');
		$removeShares->execute();
		$removeItems = \OC_DB::prepare('DELETE FROM `*PREFIX*filecache`');
		$removeItems->execute();

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

	/**
	 * @medium
	 */
	function testRemoveBrokenShares() {

		$this->prepareFileCache();

		// check if there are just 3 shares (see setUp - precondition: empty table)
		$countShares = \OC_DB::prepare('SELECT COUNT(`id`) FROM `*PREFIX*share`');
		$result = $countShares->execute()->fetchOne();
		$this->assertEquals(3, $result);

		// check if there are just 2 items (see setUp - precondition: empty table)
		$countItems = \OC_DB::prepare('SELECT COUNT(`fileid`) FROM `*PREFIX*filecache`');
		$result = $countItems->execute()->fetchOne();
		$this->assertEquals(2, $result);

		// execute actual code which should be tested
		\OC\Files\Cache\Shared_Updater::fixBrokenSharesOnAppUpdate();

		// check if there are just 2 shares (one gets killed by the code as there is no filecache entry for this)
		$countShares = \OC_DB::prepare('SELECT COUNT(`id`) FROM `*PREFIX*share`');
		$result = $countShares->execute()->fetchOne();
		$this->assertEquals(2, $result);

		// check if the share of file '200' is removed as there is no entry for this in filecache table
		$countShares = \OC_DB::prepare('SELECT COUNT(`id`) FROM `*PREFIX*share` WHERE `file_source` = 200');
		$result = $countShares->execute()->fetchOne();
		$this->assertEquals(0, $result);

		// check if there are just 2 items
		$countItems = \OC_DB::prepare('SELECT COUNT(`fileid`) FROM `*PREFIX*filecache`');
		$result = $countItems->execute()->fetchOne();
		$this->assertEquals(2, $result);
	}

	/**
	 * test update for the removal of the logical "Shared" folder. It should update
	 * the file_target for every share and create a physical "Shared" folder for each user
	 */
	function testRemoveSharedFolder() {
		self::prepareDB();
		// run the update routine to remove the shared folder and replace it with a real folder
		removeSharedFolder(false, 2);

		// verify results
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*share`');
		$result = $query->execute(array());

		$newDBContent = $result->fetchAll();

		foreach ($newDBContent as $row) {
			if ((int)$row['share_type'] === \OCP\Share::SHARE_TYPE_USER) {
				$this->assertSame('/Shared', substr($row['file_target'], 0, strlen('/Shared')));
			} else {
				$this->assertSame('/ShouldNotChange', $row['file_target']);
			}
		}

		// cleanup
		$this->cleanupSharedTable();

	}

	private function cleanupSharedTable() {
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*share`');
		$query->execute();
	}

	/**
	 * prepare sharing table for testRemoveSharedFolder()
	 */
	private function prepareDB() {
		$this->cleanupSharedTable();
		// add items except one - because this is the test case for the broken share table
		$addItems = \OC_DB::prepare('INSERT INTO `*PREFIX*share` (`share_type`, `item_type`, ' .
			'`share_with`, `uid_owner` , `file_target`) ' .
			'VALUES (?, ?, ?, ?, ?)');
		$items = array(
			array(\OCP\Share::SHARE_TYPE_USER, 'file', 'user1', 'admin' , '/foo'),
			array(\OCP\Share::SHARE_TYPE_USER, 'folder', 'user2', 'admin', '/foo2'),
			array(\OCP\Share::SHARE_TYPE_USER, 'file', 'user3', 'admin', '/foo3'),
			array(\OCP\Share::SHARE_TYPE_USER, 'folder', 'user4', 'admin', '/foo4'),
			array(\OCP\Share::SHARE_TYPE_LINK, 'file', 'user1', 'admin', '/ShouldNotChange'),
			array(\OCP\Share::SHARE_TYPE_CONTACT, 'contact', 'admin', 'user1', '/ShouldNotChange'),

			);
		foreach($items as $item) {
			// the number is used as path_hash
			$addItems->execute($item);
		}
	}

	/**
	 * prepare file cache for testRemoveBrokenShares()
	 */
	private function prepareFileCache() {
		// some previous tests didn't clean up and therefore this has to be done here
		// FIXME: DIRTY HACK - TODO: find tests, that don't clean up and fix it there
		$this->tearDown();

		// add items except one - because this is the test case for the broken share table
		$addItems = \OC_DB::prepare('INSERT INTO `*PREFIX*filecache` (`storage`, `path_hash`, ' .
			'`parent`, `mimetype`, `mimepart`, `size`, `mtime`, `storage_mtime`) ' .
			'VALUES (1, ?, 1, 1, 1, 1, 1, 1)');
		$items = array(1, 3);
		$fileIds = array();
		foreach($items as $item) {
			// the number is used as path_hash
			$addItems->execute(array($item));
			$fileIds[] = \OC_DB::insertId('*PREFIX*filecache');
		}

		$addShares = \OC_DB::prepare('INSERT INTO `*PREFIX*share` (`file_source`, `item_type`, `uid_owner`) VALUES (?, \'file\', 1)');
		// the number is used as item_source
		$addShares->execute(array($fileIds[0]));
		$addShares->execute(array(200)); // id of "deleted" file
		$addShares->execute(array($fileIds[1]));
	}

}
