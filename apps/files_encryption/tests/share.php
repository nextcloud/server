<?php
/**
 * ownCloud
 *
 * @author Florin Peter
 * @copyright 2013 Florin Peter <owncloud@florin-peter.de>
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

require_once __DIR__ . '/util.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Share
 */
class Test_Encryption_Share extends \PHPUnit_Framework_TestCase {

	const TEST_ENCRYPTION_SHARE_USER1 = "test-share-user1";
	const TEST_ENCRYPTION_SHARE_USER2 = "test-share-user2";
	const TEST_ENCRYPTION_SHARE_USER3 = "test-share-user3";
	const TEST_ENCRYPTION_SHARE_USER4 = "test-share-user4";
	const TEST_ENCRYPTION_SHARE_GROUP1 = "test-share-group1";

	public $stateFilesTrashbin;
	public $filename;
	public $dataShort;
	/**
	 * @var OC\Files\View
	 */
	public $view;
	public $folder1;
	public $subfolder;
	public $subsubfolder;

	public static function setUpBeforeClass() {
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// enable resharing
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_allow_resharing', 'yes');

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');

		// register share hooks
		\OC::registerShareHooks();
		\OCA\Files_Sharing\Helper::registerHooks();

		// Sharing related hooks
		\OCA\Encryption\Helper::registerShareHooks();

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new OCA\Files\Share\Proxy());
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// create users
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1, true);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, true);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3, true);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4, true);

		// create group and assign users
		\OC_Group::createGroup(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1);
		\OC_Group::addToGroup(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1);
		\OC_Group::addToGroup(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1);
	}

	function setUp() {
		$this->dataShort = 'hats';
		$this->view = new \OC\Files\View('/');

		$this->folder1 = '/folder1';
		$this->subfolder = '/subfolder1';
		$this->subsubfolder = '/subsubfolder1';

		$this->filename = 'share-tmp.test';

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

		// login as first user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);
	}

	function tearDown() {
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		} else {
			OC_App::disable('files_trashbin');
		}
	}

	public static function tearDownAfterClass() {
		// clean group
		\OC_Group::deleteGroup(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1);

		// cleanup users
		\OC_User::deleteUser(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);
		\OC_User::deleteUser(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);
		\OC_User::deleteUser(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);
		\OC_User::deleteUser(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4);

		\OC_Hook::clear();
		\OC_FileProxy::clearProxies();

		// Delete keys in /data/
		$view = new \OC\Files\View('/');
		$view->rmdir('public-keys');
		$view->rmdir('owncloud_private_key');
	}


	/**
	 * @medium
	 * @param bool $withTeardown
	 */
	function testShareFile($withTeardown = true) {
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user1 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// login as user1
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as admin
			\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

			// unshare the file
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
			$this->view->unlink($this->filename);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		}
	}

	/**
	 * @medium
	 * @param bool $withTeardown
	 */
	function testReShareFile($withTeardown = true) {
		$this->testShareFile(false);

		// login as user2
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// get the file info
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);

		// share the file with user3
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user2 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// login as user2
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->filename);

		// check if data is the same as previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as user1
			\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

			// unshare the file with user2
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);

			// login as admin
			\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

			// unshare the file with user1
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
			$this->view->unlink($this->filename);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		}
	}

	/**
	 * @medium
	 * @param bool $withTeardown
	 * @return array
	 */
	function testShareFolder($withTeardown = true) {
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// create folder structure
		$this->view->mkdir('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
										 . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created folder
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);

		// check if we have a valid file info
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the folder with user1
		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user1 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// login as user1
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as admin
			\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

			// unshare the folder with user1
			\OCP\Share::unshare('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files');
			$this->view->unlink($this->folder1);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		}

		return $fileInfo;
	}

	/**
	 * @medium
	 * @param bool $withTeardown
	 */
	function testReShareFolder($withTeardown = true) {
		$fileInfoFolder1 = $this->testShareFolder(false);

		// login as user2
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created folder
		$fileInfoSubFolder = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1
			. $this->subfolder);

		// check if we have a valid file info
		$this->assertTrue($fileInfoSubFolder instanceof \OC\Files\FileInfo);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file with user3
		\OCP\Share::shareItem('folder', $fileInfoSubFolder['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user3 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// login as user3
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->subfolder
			. $this->subsubfolder . '/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// get the file info
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->subfolder
			. $this->subsubfolder . '/' . $this->filename);

		// check if we have fileInfos
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// share the file with user3
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user3 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

		// login as user3
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4 . '/files/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as user2
			\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);

			// unshare the file with user3
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

			// login as user1
			\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

			// unshare the folder with user2
			\OCP\Share::unshare('folder', $fileInfoSubFolder['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

			// login as admin
			\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

			// unshare the folder1 with user1
			\OCP\Share::unshare('folder', $fileInfoFolder1['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files');
			$this->view->unlink($this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		}
	}


	function testPublicShareFile() {
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, false, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		$publicShareKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'publicShareKeyId');

		// check if share key for public exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $publicShareKeyId . '.shareKey'));

		// some hacking to simulate public link
		//$GLOBALS['app'] = 'files_sharing';
		//$GLOBALS['fileOwner'] = \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1;
		\Test_Encryption_Util::logoutHelper();

		// get file contents
		$retrievedCryptedFile = file_get_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// tear down

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $publicShareKeyId . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->chroot('/');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
	}

	/**
	 * @medium
	 */
	function testShareFileWithGroup() {
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user2 and user3 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

		// login as user1
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->chroot('/');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));

	}

	/**
	 * @large
	 */
	function testRecoveryFile() {

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		\OCA\Encryption\Helper::adminEnableRecovery(null, 'test123');
		$recoveryKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryKeyId');

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		$util = new \OCA\Encryption\Util(new \OC\Files\View('/'), \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if recovery password match
		$this->assertTrue($util->checkRecoveryPassword('test123'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));
		$util->addRecoveryKeys();

		// create folder structure
		$this->view->mkdir('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
										  . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile1));
		$this->assertTrue(is_int($cryptedFile2));

		// check if share key for admin and recovery exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// disable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(0));

		// remove all recovery keys
		$util->removeRecoveryKeys('/');

		// check if share key for recovery not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// add recovery keys again
		$util->addRecoveryKeys('/');

		// check if share key for admin and recovery exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->unlink($this->folder1);
		$this->view->chroot('/');

		// check if share key for recovery not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		$this->assertTrue(\OCA\Encryption\Helper::adminEnableRecovery(null, 'test123'));
		$this->assertTrue(\OCA\Encryption\Helper::adminDisableRecovery('test123'));
		$this->assertEquals(0, \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryAdminEnabled'));
	}

	/**
	 * @large
	 */
	function testRecoveryForUser() {

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		$result = \OCA\Encryption\Helper::adminEnableRecovery(null, 'test123');
		$this->assertTrue($result);

		$recoveryKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryKeyId');

		// login as user2
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		$util = new \OCA\Encryption\Util(new \OC\Files\View('/'), \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// add recovery keys for existing files (e.g. the auto-generated welcome.txt)
		$util->addRecoveryKeys();

		// create folder structure
		$this->view->mkdir('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2. '/files/' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
										  . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile1));
		$this->assertTrue(is_int($cryptedFile2));

		// check if share key for user and recovery exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// change password
		\OC_User::setPassword(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, 'test', 'test123');
		$params = array('uid' => \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2,
			'password' => 'test',
			'recoveryPassword' => 'test123');
		\OCA\Encryption\Hooks::setPassphrase($params);

		// login as user2
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, false, 'test');

		// get file contents
		$retrievedCryptedFile1 = file_get_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);
		$retrievedCryptedFile2 = file_get_contents(
			'crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile1);
		$this->assertEquals($this->dataShort, $retrievedCryptedFile2);

		// cleanup
		$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/');
		$this->view->unlink($this->folder1);
		$this->view->unlink($this->filename);
		$this->view->chroot('/');

		// check if share key for user and recovery exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/share-keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(0));

		\OCA\Encryption\Helper::adminDisableRecovery('test123');
		$this->assertEquals(0, \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryAdminEnabled'));

		//clean up, reset passwords
		\OC_User::setPassword(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, 'test123');
		$params = array('uid' => \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2,
			'password' => \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2,
			'recoveryPassword' => 'test123');
		\OCA\Encryption\Hooks::setPassphrase($params);
	}

	/**
	 * @medium
	 */
	function testFailShareFile() {
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// break users public key
		$this->view->rename('/public-keys/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.public.key',
			'/public-keys/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.public.key_backup');

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		try {
			\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1, OCP\PERMISSION_ALL);
		} catch (Exception $e) {
			$this->assertEquals(0, strpos($e->getMessage(), "Following users are not set up for encryption"));
		}


		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user1 not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// break user1 public key
		$this->view->rename(
			'/public-keys/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.public.key_backup',
			'/public-keys/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.public.key');

		// remove share file
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
							. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3
							. '.shareKey');

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// unshare the file with user1
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_GROUP1);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->chroot('/');
	}


	/**
	 * test moving a shared file out of the Shared folder
	 */
	function testRename() {

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, OCP\PERMISSION_ALL);

		// check if share key for user2 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));


		// login as user2
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		$this->assertTrue($this->view->file_exists('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename));

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// move the file to a subfolder
		$this->view->rename('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename,
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1 . $this->filename);

		// check if we can read the moved file
		$retrievedRenamedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1 .  $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedRenamedFile);

		// cleanup
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);
	}

	/**
	 * test if additional share keys are added if we move a folder to a shared parent
	 * @medium
	 */
	function testMoveFolder() {

		$view = new \OC\Files\View('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		$filename = '/tmp-' . uniqid();
		$folder = '/folder' . uniqid();

		\OC\Files\Filesystem::mkdir($folder);

		// Save long data as encrypted file using stream wrapper
		$cryptedFile = \OC\Files\Filesystem::file_put_contents($folder . $filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// Get file decrypted contents
		$decrypt = \OC\Files\Filesystem::file_get_contents($folder . $filename);

		$this->assertEquals($this->dataShort, $decrypt);

		$newFolder = '/newfolder/subfolder' . uniqid();
		\OC\Files\Filesystem::mkdir('/newfolder');

		// get the file info from previous created file
		$fileInfo = \OC\Files\Filesystem::getFileInfo('/newfolder');
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// share the folder
		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, OCP\PERMISSION_ALL);

		\OC\Files\Filesystem::rename($folder, $newFolder);

		// Get file decrypted contents
		$newDecrypt = \OC\Files\Filesystem::file_get_contents($newFolder . $filename);
		$this->assertEquals($this->dataShort, $newDecrypt);

		// check if additional share key for user2 exists
		$this->assertTrue($view->file_exists('files_encryption/share-keys' . $newFolder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// check that old keys were removed/moved properly
		$this->assertFalse($view->file_exists('files_encryption/share-keys' . $folder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// tear down
		\OC\Files\Filesystem::unlink($newFolder);
		\OC\Files\Filesystem::unlink('/newfolder');
	}

	function usersProvider() {
		return array(
			// test as owner
			array(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1),
			// test as share receiver
			array(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2),
		);
	}

	/**
	 * @dataProvider usersProvider
	 */
	function testMoveFileToFolder($userId) {
		$view = new \OC\Files\View('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		$filename = '/tmp-' . uniqid();
		$folder = '/folder' . uniqid();

		\OC\Files\Filesystem::mkdir($folder);

		// Save long data as encrypted file using stream wrapper
		$cryptedFile = \OC\Files\Filesystem::file_put_contents($folder . $filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// Get file decrypted contents
		$decrypt = \OC\Files\Filesystem::file_get_contents($folder . $filename);

		$this->assertEquals($this->dataShort, $decrypt);

		$subFolder = $folder . '/subfolder' . uniqid();
		\OC\Files\Filesystem::mkdir($subFolder);

		// get the file info from previous created file
		$fileInfo = \OC\Files\Filesystem::getFileInfo($folder);
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// share the folder
		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, OCP\PERMISSION_ALL);

		// check that the share keys exist
		$this->assertTrue($view->file_exists('files_encryption/share-keys' . $folder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($view->file_exists('files_encryption/share-keys' . $folder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// move the file into the subfolder as the test user
		\Test_Encryption_Util::loginHelper($userId);
		\OC\Files\Filesystem::rename($folder . $filename, $subFolder . $filename);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// Get file decrypted contents
		$newDecrypt = \OC\Files\Filesystem::file_get_contents($subFolder . $filename);
		$this->assertEquals($this->dataShort, $newDecrypt);

		// check if additional share key for user2 exists
		$this->assertTrue($view->file_exists('files_encryption/share-keys' . $subFolder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($view->file_exists('files_encryption/share-keys' . $subFolder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// check that old keys were removed/moved properly
		$this->assertFalse($view->file_exists('files_encryption/share-keys' . $folder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertFalse($view->file_exists('files_encryption/share-keys' . $folder . '/' . $filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// tear down
		\OC\Files\Filesystem::unlink($subFolder);
		\OC\Files\Filesystem::unlink($folder);
	}

}
