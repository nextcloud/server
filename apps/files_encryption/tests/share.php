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

require_once realpath(dirname(__FILE__) . '/../3rdparty/Crypt_Blowfish/Blowfish.php');
require_once realpath(dirname(__FILE__) . '/../../../lib/base.php');
require_once realpath(dirname(__FILE__) . '/../lib/crypt.php');
require_once realpath(dirname(__FILE__) . '/../lib/keymanager.php');
require_once realpath(dirname(__FILE__) . '/../lib/proxy.php');
require_once realpath(dirname(__FILE__) . '/../lib/stream.php');
require_once realpath(dirname(__FILE__) . '/../lib/util.php');
require_once realpath(dirname(__FILE__) . '/../lib/helper.php');
require_once realpath(dirname(__FILE__) . '/../appinfo/app.php');
require_once realpath(dirname(__FILE__) . '/util.php');

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
	 * @var OC_FilesystemView
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
		\OC_Appconfig::setValue('core', 'shareapi_allow_resharing', 'yes');

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks();
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');

		// Sharing related hooks
		\OCA\Encryption\Helper::registerShareHooks();

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
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
		$this->view = new \OC_FilesystemView('/');

		$this->folder1 = '/folder1';
		$this->subfolder = '/subfolder1';
		$this->subsubfolder = '/subsubfolder1';

		$this->filename = 'share-tmp.test';

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');
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
	}

	/**
	 * @medium
	 * @param bool $withTeardown
	 */
	function testShareFile($withTeardown = true) {
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

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
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/Shared/' . $this->filename);

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
			$this->view->unlink(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

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

		// login as user1
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// get the file info
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/Shared/' . $this->filename);

		// share the file with user2
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
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/Shared/' . $this->filename);

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
			$this->view->unlink(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

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
		$cryptedFile = file_put_contents('crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
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
		$this->assertTrue(is_array($fileInfo));

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
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/Shared' . $this->folder1
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
			$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);

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

		// login as user1
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created folder
		$fileInfoSubFolder = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files/Shared' . $this->folder1
			. $this->subfolder);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfoSubFolder));

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file with user2
		\OCP\Share::shareItem('folder', $fileInfoSubFolder['fileid'], \OCP\Share::SHARE_TYPE_USER, \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user2 exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '.' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// login as user2
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/Shared' . $this->subfolder
			. $this->subsubfolder . '/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// get the file info
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/Shared' . $this->subfolder
			. $this->subsubfolder . '/' . $this->filename);

		// check if we have fileInfos
		$this->assertTrue(is_array($fileInfo));

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
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER4 . '/files/Shared/' . $this->filename);

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
			$this->view->unlink(
				'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder
				. $this->subsubfolder . '/' . $this->filename);

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
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, false, OCP\PERMISSION_ALL);

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		$publicShareKeyId = \OC_Appconfig::getValue('files_encryption', 'publicShareKeyId');

		// check if share key for public exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . $publicShareKeyId . '.shareKey'));

		// some hacking to simulate public link
		$GLOBALS['app'] = 'files_sharing';
		$GLOBALS['fileOwner'] = \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1;
		\OC_User::setUserId(false);

		// get file contents
		$retrievedCryptedFile = file_get_contents('crypt://' . $this->filename);

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
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

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
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

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
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER3 . '/files/Shared/' . $this->filename);

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
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

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
		$recoveryKeyId = OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');

		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		$util = new \OCA\Encryption\Util(new \OC_FilesystemView('/'), \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// check if recovery password match
		$this->assertTrue($util->checkRecoveryPassword('test123'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// create folder structure
		$this->view->mkdir('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt://' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
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

		// remove all recovery keys
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
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->folder1);

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
		$this->assertEquals(0, \OC_Appconfig::getValue('files_encryption', 'recoveryAdminEnabled'));
	}

	/**
	 * @large
	 */
	function testRecoveryForUser() {
		$this->markTestIncomplete(
			'This test drives Jenkins crazy - "Cannot modify header information - headers already sent" - line 811'
		);
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		\OCA\Encryption\Helper::adminEnableRecovery(null, 'test123');
		$recoveryKeyId = OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');

		// login as user1
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		$util = new \OCA\Encryption\Util(new \OC_FilesystemView('/'), \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2);

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// create folder structure
		$this->view->mkdir('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt://' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
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

		// login as user1
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2, false, 'test');

		// get file contents
		$retrievedCryptedFile1 = file_get_contents('crypt://' . $this->filename);
		$retrievedCryptedFile2 = file_get_contents(
			'crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile1);
		$this->assertEquals($this->dataShort, $retrievedCryptedFile2);

		// cleanup
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1);
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->filename);

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
		$this->assertEquals(0, \OC_Appconfig::getValue('files_encryption', 'recoveryAdminEnabled'));
	}

	/**
	 * @medium
	 */
	function testFailShareFile() {
		// login as admin
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

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
		$this->view->unlink('/' . \Test_Encryption_Share::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);
	}

}
