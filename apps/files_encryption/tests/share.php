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

use OCA\Encryption;

/**
 * Class Test_Encryption_Share
 */
class Test_Encryption_Share extends \PHPUnit_Framework_TestCase
{

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

	function setUp()
	{
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		$this->dataShort = 'hats';
		$this->view = new \OC_FilesystemView('/');

		$userHome = \OC_User::getHome('admin');
		$this->dataDir = str_replace('/admin', '', $userHome);

		$this->folder1 = '/folder1';
		$this->subfolder = '/subfolder1';
		$this->subsubfolder = '/subsubfolder1';

		$this->filename = 'share-tmp.test';

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

		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

		// create users
		$this->loginHelper('user1', true);
		$this->loginHelper('user2', true);
		$this->loginHelper('user3', true);

		// create group and assign users
		\OC_Group::createGroup('group1');
		\OC_Group::addToGroup('user2', 'group1');
		\OC_Group::addToGroup('user3', 'group1');
	}

	function tearDown()
	{
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		} else {
			OC_App::disable('files_trashbin');
		}

		// clean group
		\OC_Group::deleteGroup('group1');

		// cleanup users
		\OC_User::deleteUser('user1');
		\OC_User::deleteUser('user2');
		\OC_User::deleteUser('user3');

		\OC_FileProxy::clearProxies();
	}

	/**
	 * @param bool $withTeardown
	 */
	function testShareFile($withTeardown = true)
	{
		// login as admin
		$this->loginHelper('admin');

		// save file with content
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo('/admin/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1', OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		// check if share key for user1 exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user1.shareKey'));

		// login as user1
		$this->loginHelper('user1');

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents('/user1/files/Shared/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as admin
			$this->loginHelper('admin');

			// unshare the file
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user1.shareKey'));

			// cleanup
			$this->view->unlink('/admin/files/' . $this->filename);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.admin.shareKey'));
		}
	}

	/**
	 * @param bool $withTeardown
	 */
	function testReShareFile($withTeardown = true)
	{
		$this->testShareFile(false);

		// login as user1
		$this->loginHelper('user1');

		// get the file info
		$fileInfo = $this->view->getFileInfo('/user1/files/Shared/' . $this->filename);

		// share the file with user2
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user2', OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		// check if share key for user2 exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user2.shareKey'));

		// login as user2
		$this->loginHelper('user2');

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents('/user2/files/Shared/' . $this->filename);

		// check if data is the same as previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as user1
			$this->loginHelper('user1');

			// unshare the file with user2
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user2');

			// login as admin
			$this->loginHelper('admin');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user2.shareKey'));

			// unshare the file with user1
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user1.shareKey'));

			// cleanup
			$this->view->unlink('/admin/files/' . $this->filename);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.admin.shareKey'));
		}
	}

	/**
	 * @param bool $withTeardown
	 * @return array
	 */
	function testShareFolder($withTeardown = true)
	{
		// login as admin
		$this->loginHelper('admin');

		// create folder structure
		$this->view->mkdir('/admin/files' . $this->folder1);
		$this->view->mkdir('/admin/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir('/admin/files' . $this->folder1 . $this->subfolder . $this->subsubfolder);

		// save file with content
		$cryptedFile = file_put_contents('crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created folder
		$fileInfo = $this->view->getFileInfo('/admin/files' . $this->folder1);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the folder with user1
		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1', OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		// check if share key for user1 exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user1.shareKey'));

		// login as user1
		$this->loginHelper('user1');

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents('/user1/files/Shared' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as admin
			$this->loginHelper('admin');

			// unshare the folder with user1
			\OCP\Share::unshare('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user1.shareKey'));

			// cleanup
			$this->view->unlink('/admin/files' . $this->folder1);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.admin.shareKey'));
		}

		return $fileInfo;
	}

	/**
	 * @param bool $withTeardown
	 */
	function testReShareFolder($withTeardown = true)
	{
		$fileInfoFolder1 = $this->testShareFolder(false);

		// login as user1
		$this->loginHelper('user1');

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created folder
		$fileInfoSubFolder = $this->view->getFileInfo('/user1/files/Shared' . $this->folder1 . $this->subfolder);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfoSubFolder));

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file with user2
		\OCP\Share::shareItem('folder', $fileInfoSubFolder['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user2', OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		// check if share key for user2 exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user2.shareKey'));

		// login as user2
		$this->loginHelper('user2');

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents('/user2/files/Shared' . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// get the file info
		$fileInfo = $this->view->getFileInfo('/user2/files/Shared' . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if we have fileInfos
		$this->assertTrue(is_array($fileInfo));

		// share the file with user3
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user3', OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		// check if share key for user3 exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user3.shareKey'));

		// login as user3
		$this->loginHelper('user3');

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents('/user3/files/Shared/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as user2
			$this->loginHelper('user2');

			// unshare the file with user3
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user3');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user3.shareKey'));

			// login as user1
			$this->loginHelper('user1');

			// unshare the folder with user2
			\OCP\Share::unshare('folder', $fileInfoSubFolder['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user2');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user2.shareKey'));

			// login as admin
			$this->loginHelper('admin');

			// unshare the folder1 with user1
			\OCP\Share::unshare('folder', $fileInfoFolder1['fileid'], \OCP\Share::SHARE_TYPE_USER, 'user1');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user1.shareKey'));

			// cleanup
			$this->view->unlink('/admin/files' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.admin.shareKey'));
		}
	}

	function testPublicShareFile()
	{
		// login as admin
		$this->loginHelper('admin');

		// save file with content
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo('/admin/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, false, OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		$publicShareKeyId = \OC_Appconfig::getValue('files_encryption', 'publicShareKeyId');

		// check if share key for public exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.' . $publicShareKeyId . '.shareKey'));

		// some hacking to simulate public link
		$GLOBALS['app'] = 'files_sharing';
		$GLOBALS['fileOwner'] = 'admin';
		\OC_User::setUserId('');

		// get file contents
		$retrievedCryptedFile = file_get_contents('crypt://' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// tear down

		// login as admin
		$this->loginHelper('admin');

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.' . $publicShareKeyId . '.shareKey'));

		// cleanup
		$this->view->unlink('/admin/files/' . $this->filename);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.admin.shareKey'));
	}

	function testShareFileWithGroup()
	{
		// login as admin
		$this->loginHelper('admin');

		// save file with content
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo('/admin/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'group1', OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		// check if share key for user2 and user3 exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user2.shareKey'));
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user3.shareKey'));

		// login as user1
		$this->loginHelper('user2');

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents('/user2/files/Shared/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// login as admin
		$this->loginHelper('admin');

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'group1');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user2.shareKey'));
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user3.shareKey'));

		// cleanup
		$this->view->unlink('/admin/files/' . $this->filename);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.admin.shareKey'));

	}

	function testRecoveryFile()
	{
		\OCA\Encryption\Helper::adminEnableRecovery(null, 'test123');
		$recoveryKeyId = OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');

		// check if control file created
		$this->assertTrue($this->view->file_exists('/control-file/controlfile.enc'));

		// login as admin
		$this->loginHelper('admin');

		$util = new \OCA\Encryption\Util(new \OC_FilesystemView('/'), 'admin');

		// check if recovery password match
		$this->assertTrue($util->checkRecoveryPassword('test123'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// create folder structure
		$this->view->mkdir('/admin/files' . $this->folder1);
		$this->view->mkdir('/admin/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir('/admin/files' . $this->folder1 . $this->subfolder . $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt://' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile1));
		$this->assertTrue(is_int($cryptedFile2));

		// check if share key for admin and recovery exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.admin.shareKey'));
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.admin.shareKey'));
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// disable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(0));

		// remove all recovery keys
		$util->removeRecoveryKeys('/');

		// check if share key for recovery not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// remove all recovery keys
		$util->addRecoveryKeys('/');

		// check if share key for admin and recovery exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// cleanup
		$this->view->unlink('/admin/files/' . $this->filename);
		$this->view->unlink('/admin/files/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if share key for recovery not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		$this->assertTrue(\OCA\Encryption\Helper::adminEnableRecovery(null, 'test123'));
		$this->assertTrue(\OCA\Encryption\Helper::adminDisableRecovery('test123'));
		$this->assertEquals(0, \OC_Appconfig::getValue('files_encryption', 'recoveryAdminEnabled'));
	}

	function testRecoveryForUser()
	{
		// login as admin
		$this->loginHelper('admin');

		\OCA\Encryption\Helper::adminEnableRecovery(null, 'test123');
		$recoveryKeyId = OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');

		// check if control file created
		$this->assertTrue($this->view->file_exists('/control-file/controlfile.enc'));

		// login as user1
		$this->loginHelper('user1');

		$util = new \OCA\Encryption\Util(new \OC_FilesystemView('/'), 'user1');

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// create folder structure
		$this->view->mkdir('/user1/files' . $this->folder1);
		$this->view->mkdir('/user1/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir('/user1/files' . $this->folder1 . $this->subfolder . $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt://' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile1));
		$this->assertTrue(is_int($cryptedFile2));

		// check if share key for user and recovery exists
		$this->assertTrue($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->filename . '.user1.shareKey'));
		$this->assertTrue($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user1.shareKey'));
		$this->assertTrue($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// login as admin
		$this->loginHelper('admin');

		// change password
		\OC_User::setPassword('user1', 'test', 'test123');

		// login as user1
		$this->loginHelper('user1', false, 'test');

		// get file contents
		$retrievedCryptedFile1 = file_get_contents('crypt://' . $this->filename);
		$retrievedCryptedFile2 = file_get_contents('crypt://' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile1);
		$this->assertEquals($this->dataShort, $retrievedCryptedFile2);

		// cleanup
		$this->view->unlink('/user1/files' . $this->folder1);
		$this->view->unlink('/user1/files' . $this->filename);

		// check if share key for user and recovery exists
		$this->assertFalse($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->filename . '.user1.shareKey'));
		$this->assertFalse($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.user1.shareKey'));
		$this->assertFalse($this->view->file_exists('/user1/files_encryption/share-keys/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename . '.' . $recoveryKeyId . '.shareKey'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(0));

		\OCA\Encryption\Helper::adminDisableRecovery('test123');
		$this->assertEquals(0, \OC_Appconfig::getValue('files_encryption', 'recoveryAdminEnabled'));
	}

	function testFailShareFile()
	{
		// login as admin
		$this->loginHelper('admin');

		// save file with content
		$cryptedFile = file_put_contents('crypt://' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo('/admin/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// break users public key
		$this->view->rename('/public-keys/user2.public.key', '/public-keys/user2.public.key_backup');

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'group1', OCP\PERMISSION_ALL);

		// login as admin
		$this->loginHelper('admin');

		// check if share key for user1 not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user2.shareKey'));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// break user1 public key
		$this->view->rename('/public-keys/user2.public.key_backup', '/public-keys/user2.public.key');

		// remove share file
		$this->view->unlink('/admin/files_encryption/share-keys/' . $this->filename . '.user2.shareKey');

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// unshare the file with user1
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, 'group1');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $this->filename . '.user2.shareKey'));

		// cleanup
		$this->view->unlink('/admin/files/' . $this->filename);
	}



	/**
	 * @param $user
	 * @param bool $create
	 * @param bool $password
	 */
	function loginHelper($user, $create = false, $password = false)
	{
		if ($create) {
			\OC_User::createUser($user, $user);
		}

		if ($password === false) {
			$password = $user;
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_Util::setupFS($user);
		\OC_User::setUserId($user);

		$params['uid'] = $user;
		$params['password'] = $password;
		OCA\Encryption\Hooks::login($params);
	}
}
