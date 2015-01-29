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

namespace OCA\Files_Encryption\Tests;

/**
 * Class Share
 */
class Share extends TestCase {

	const TEST_ENCRYPTION_SHARE_USER1 = "test-share-user1";
	const TEST_ENCRYPTION_SHARE_USER2 = "test-share-user2";
	const TEST_ENCRYPTION_SHARE_USER3 = "test-share-user3";
	const TEST_ENCRYPTION_SHARE_USER4 = "test-share-user4";
	const TEST_ENCRYPTION_SHARE_GROUP1 = "test-share-group1";

	public $stateFilesTrashbin;
	public $filename;
	public $dataShort;
	/**
	 * @var \OC\Files\View
	 */
	public $view;
	public $folder1;
	public $subfolder;
	public $subsubfolder;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// enable resharing
		\OC::$server->getAppConfig()->setValue('core', 'shareapi_allow_resharing', 'yes');

		// register share hooks
		\OC::registerShareHooks();
		\OCA\Files_Sharing\Helper::registerHooks();

		// clear and register hooks
		\OC_FileProxy::register(new \OCA\Files\Share\Proxy());

		// create users
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1, true);
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2, true);
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER3, true);
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER4, true);

		// create group and assign users
		\OC_Group::createGroup(self::TEST_ENCRYPTION_SHARE_GROUP1);
		\OC_Group::addToGroup(self::TEST_ENCRYPTION_SHARE_USER3, self::TEST_ENCRYPTION_SHARE_GROUP1);
		\OC_Group::addToGroup(self::TEST_ENCRYPTION_SHARE_USER4, self::TEST_ENCRYPTION_SHARE_GROUP1);
	}

	protected function setUp() {
		parent::setUp();

		$this->dataShort = 'hats';
		$this->view = new \OC\Files\View('/');

		$this->folder1 = '/folder1';
		$this->subfolder = '/subfolder1';
		$this->subsubfolder = '/subsubfolder1';

		$this->filename = 'share-tmp.test';

		// remember files_trashbin state
		$this->stateFilesTrashbin = \OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

		// login as first user
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		$this->createMocks();
	}

	protected function tearDown() {
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			\OC_App::enable('files_trashbin');
		} else {
			\OC_App::disable('files_trashbin');
		}

		$this->restoreHttpHelper();

		parent::tearDown();
	}

	public static function tearDownAfterClass() {
		// clean group
		\OC_Group::deleteGroup(self::TEST_ENCRYPTION_SHARE_GROUP1);

		// cleanup users
		\OC_User::deleteUser(self::TEST_ENCRYPTION_SHARE_USER1);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_SHARE_USER2);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_SHARE_USER3);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_SHARE_USER4);

		parent::tearDownAfterClass();
	}

	private function createMocks() {
		$config = $this->getMockBuilder('\OCP\IConfig')
				->disableOriginalConstructor()->getMock();
		$certificateManager = $this->getMock('\OCP\ICertificateManager');
		$httpHelperMock = $this->getMockBuilder('\OC\HTTPHelper')
				->setConstructorArgs(array($config, $certificateManager))
				->getMock();
		$httpHelperMock->expects($this->any())->method('post')->with($this->anything())->will($this->returnValue(array('success' => true, 'result' => "{'ocs' : { 'meta' : { 'statuscode' : 100 }}}")));

		$this->registerHttpHelper($httpHelperMock);
	}

	/**
	 * Register an http helper mock for testing purposes.
	 * @param $httpHelper http helper mock
	 */
	private function registerHttpHelper($httpHelper) {
		$this->oldHttpHelper = \OC::$server->query('HTTPHelper');
		\OC::$server->registerService('HTTPHelper', function ($c) use ($httpHelper) {
			return $httpHelper;
		});
	}

	/**
	 * Restore the original http helper
	 */
	private function restoreHttpHelper() {
		$oldHttpHelper = $this->oldHttpHelper;
		\OC::$server->registerService('HTTPHelper', function ($c) use ($oldHttpHelper) {
			return $oldHttpHelper;
		});
	}

	/**
	 * @medium
	 */
	function testDeclineServer2ServerShare() {

		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);


		// share the file
		$token = \OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, '', \OCP\Constants::PERMISSION_ALL);
		$this->assertTrue(is_string($token));

		$publicShareKeyId = \OC::$server->getConfig()->getAppValue('files_encryption', 'publicShareKeyId');

		// check if share key for public exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $publicShareKeyId . '.shareKey'));

		// manipulate share
		$query = \OC::$server->getDatabaseConnection()->prepare('UPDATE `*PREFIX*share` SET `share_type` = ?, `share_with` = ? WHERE `token`=?');
		$this->assertTrue($query->execute(array(\OCP\Share::SHARE_TYPE_REMOTE, 'foo@bar', $token)));

		// check if share key not exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $publicShareKeyId . '.shareKey'));


		$query = \OC::$server->getDatabaseConnection()->prepare('SELECT * FROM `*PREFIX*share` WHERE `token`=?');
		$query->execute(array($token));

		$share = $query->fetch();

		$_POST['token'] = $token;
		$s2s = new \OCA\Files_Sharing\API\Server2Server();
		$s2s->declineShare(array('id' => $share['id']));

		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $publicShareKeyId . '.shareKey'));

	}

	/**
	 * @medium
	 * @param bool $withTeardown
	 */
	function testShareFile($withTeardown = true) {
		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2, \OCP\Constants::PERMISSION_ALL);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user1 exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// login as user1
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as admin
			self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

			// unshare the file
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
			$this->view->unlink($this->filename);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		}
	}

	function testDownloadVersions() {
		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		$rootView = new \OC\Files\View();

		// save file twice to create a new version
		\OC\Files\Filesystem::file_put_contents($this->filename, "revision1");
		\OCA\Files_Versions\Storage::store($this->filename);
		\OC\Files\Filesystem::file_put_contents($this->filename, "revision2");

		// check if the owner can retrieve the correct version
		$versions = \OCA\Files_Versions\Storage::getVersions(self::TEST_ENCRYPTION_SHARE_USER1, $this->filename);
		$this->assertSame(1, count($versions));
		$version = reset($versions);
		$versionUser1 = $rootView->file_get_contents('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_versions/' . $this->filename . '.v' . $version['version']);
		$this->assertSame('revision1', $versionUser1);

		// share the file
		$fileInfo = \OC\Files\Filesystem::getFileInfo($this->filename);
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);
		$this->assertTrue(\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2, \OCP\Constants::PERMISSION_ALL));

		// try to download the version as user2
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);
		$versionUser2 = $rootView->file_get_contents('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_versions/' . $this->filename . '.v' . $version['version']);
		$this->assertSame('revision1', $versionUser2);

		//cleanup
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2);
		\OC\Files\Filesystem::unlink($this->filename);
	}

	/**
	 * @medium
	 * @param bool $withTeardown
	 */
	function testReShareFile($withTeardown = true) {
		$this->testShareFile(false);

		// login as user2
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

		// get the file info
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);

		// share the file with user3
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER3, \OCP\Constants::PERMISSION_ALL);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user2 exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// login as user2
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER3);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->filename);

		// check if data is the same as previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as user1
			self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

			// unshare the file with user2
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER3);

			// login as admin
			self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

			// unshare the file with user1
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
			$this->view->unlink($this->filename);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		}
	}

	/**
	 * @medium
	 * @param bool $withTeardown
	 * @return array
	 */
	function testShareFolder($withTeardown = true) {
		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// create folder structure
		$this->view->mkdir('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
			. $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created folder
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the folder with user1
		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2, \OCP\Constants::PERMISSION_ALL);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user1 exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// login as user1
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as admin
			self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

			// unshare the folder with user1
			\OCP\Share::unshare('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files');
			$this->view->unlink($this->folder1);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
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
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created folder
		$fileInfoSubFolder = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1
			. $this->subfolder);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfoSubFolder);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file with user3
		\OCP\Share::shareItem('folder', $fileInfoSubFolder['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER3, \OCP\Constants::PERMISSION_ALL);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user3 exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// login as user3
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER3);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->subfolder
			. $this->subsubfolder . '/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// get the file info
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->subfolder
			. $this->subsubfolder . '/' . $this->filename);

		// check if we have fileInfos
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// share the file with user3
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER4, \OCP\Constants::PERMISSION_ALL);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user3 exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

		// login as user3
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER4);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER4 . '/files/' . $this->filename);

		// check if data is the same
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// cleanup
		if ($withTeardown) {

			// login as user2
			self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER3);

			// unshare the file with user3
			\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER4);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

			// login as user1
			self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

			// unshare the folder with user2
			\OCP\Share::unshare('folder', $fileInfoSubFolder['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER3);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

			// login as admin
			self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

			// unshare the folder1 with user1
			\OCP\Share::unshare('folder', $fileInfoFolder1['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2);

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

			// cleanup
			$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files');
			$this->view->unlink($this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);
			$this->view->chroot('/');

			// check if share key not exists
			$this->assertFalse($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys'
				. $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		}
	}


	function testRemoteShareFile() {
		// login as admin
		//self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_REMOTE, 'user1@server1', \OCP\Constants::PERMISSION_ALL);

		$publicShareKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'publicShareKeyId');

		// check if share key for public exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $publicShareKeyId . '.shareKey'));

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_REMOTE, 'user1@server1');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $publicShareKeyId . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->chroot('/');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
	}

	function testPublicShareFile() {
		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, false, \OCP\Constants::PERMISSION_ALL);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		$publicShareKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'publicShareKeyId');

		// check if share key for public exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $publicShareKeyId . '.shareKey'));

		// some hacking to simulate public link
		//$GLOBALS['app'] = 'files_sharing';
		//$GLOBALS['fileOwner'] = self::TEST_ENCRYPTION_SHARE_USER1;
		self::logoutHelper();

		// get file contents
		$retrievedCryptedFile = file_get_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/'  . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// tear down

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_LINK, null);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $publicShareKeyId . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->chroot('/');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
	}

	/**
	 * @medium
	 */
	function testShareFileWithGroup() {
		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, self::TEST_ENCRYPTION_SHARE_GROUP1, \OCP\Constants::PERMISSION_ALL);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user2 and user3 exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

		// login as user1
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER3);

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER3 . '/files/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, self::TEST_ENCRYPTION_SHARE_GROUP1);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->chroot('/');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));

	}

	/**
	 * @large
	 */
	function testRecoveryFile() {

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		\OCA\Files_Encryption\Helper::adminEnableRecovery(null, 'test123');
		$recoveryKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryKeyId');

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		$util = new \OCA\Files_Encryption\Util(new \OC\Files\View('/'), self::TEST_ENCRYPTION_SHARE_USER1);

		// check if recovery password match
		$this->assertTrue($util->checkRecoveryPassword('test123'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));
		$util->addRecoveryKeys();

		// create folder structure
		$this->view->mkdir('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
			. $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile1);
		$this->assertInternalType('int', $cryptedFile2);

		// check if share key for admin and recovery exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));

		// disable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(0));

		// remove all recovery keys
		$util->removeRecoveryKeys('/');

		// check if share key for recovery not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// add recovery keys again
		$util->addRecoveryKeys('/');

		// check if share key for admin and recovery exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->unlink($this->folder1);
		$this->view->chroot('/');

		// check if share key for recovery not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));

		$this->assertTrue(\OCA\Files_Encryption\Helper::adminEnableRecovery(null, 'test123'));
		$this->assertTrue(\OCA\Files_Encryption\Helper::adminDisableRecovery('test123'));
		$this->assertEquals(0, \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryAdminEnabled'));
	}

	/**
	 * @large
	 */
	function testRecoveryForUser() {

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		$result = \OCA\Files_Encryption\Helper::adminEnableRecovery(null, 'test123');
		$this->assertTrue($result);

		$recoveryKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryKeyId');

		// login as user2
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

		$util = new \OCA\Files_Encryption\Util(new \OC\Files\View('/'), self::TEST_ENCRYPTION_SHARE_USER2);

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(1));

		// add recovery keys for existing files (e.g. the auto-generated welcome.txt)
		$util->addRecoveryKeys();

		// create folder structure
		$this->view->mkdir('/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1);
		$this->view->mkdir(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder);
		$this->view->mkdir(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder
			. $this->subsubfolder);

		// save file with content
		$cryptedFile1 = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER2. '/files/' . $this->filename, $this->dataShort);
		$cryptedFile2 = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/'
			. $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile1);
		$this->assertInternalType('int', $cryptedFile2);

		// check if share key for user and recovery exists
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// change password
		\OC_User::setPassword(self::TEST_ENCRYPTION_SHARE_USER2, 'test', 'test123');
		$params = array('uid' => self::TEST_ENCRYPTION_SHARE_USER2,
			'password' => 'test',
			'recoveryPassword' => 'test123');
		\OCA\Files_Encryption\Hooks::setPassphrase($params);

		// login as user2
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2, false, 'test');

		// get file contents
		$retrievedCryptedFile1 = file_get_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);
		$retrievedCryptedFile2 = file_get_contents(
			'crypt:///' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files' . $this->folder1 . $this->subfolder . $this->subsubfolder . '/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile1);
		$this->assertEquals($this->dataShort, $retrievedCryptedFile2);

		// cleanup
		$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/');
		$this->view->unlink($this->folder1);
		$this->view->unlink($this->filename);
		$this->view->chroot('/');

		// check if share key for user and recovery exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files_encryption/keys/' . $this->folder1
			. $this->subfolder . $this->subsubfolder . '/'
			. $this->filename . '/' . $recoveryKeyId . '.shareKey'));

		// enable recovery for admin
		$this->assertTrue($util->setRecoveryForUser(0));

		\OCA\Files_Encryption\Helper::adminDisableRecovery('test123');
		$this->assertEquals(0, \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryAdminEnabled'));

		//clean up, reset passwords
		\OC_User::setPassword(self::TEST_ENCRYPTION_SHARE_USER2, self::TEST_ENCRYPTION_SHARE_USER2, 'test123');
		$params = array('uid' => self::TEST_ENCRYPTION_SHARE_USER2,
			'password' => self::TEST_ENCRYPTION_SHARE_USER2,
			'recoveryPassword' => 'test123');
		\OCA\Files_Encryption\Hooks::setPassphrase($params);
	}

	/**
	 * @medium
	 */
	function testFailShareFile() {
		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// check if the unencrypted file size is stored
		$this->assertGreaterThan(0, $fileInfo['unencrypted_size']);

		// break users public key
		$this->view->rename(\OCA\Files_Encryption\Keymanager::getPublicKeyPath() . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.publicKey',
			\OCA\Files_Encryption\Keymanager::getPublicKeyPath() . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.publicKey_backup');

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// share the file
		try {
			\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, self::TEST_ENCRYPTION_SHARE_GROUP1, \OCP\Constants::PERMISSION_ALL);
		} catch (\Exception $e) {
			$this->assertEquals(0, strpos($e->getMessage(), "Following users are not set up for encryption"));
		}


		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// check if share key for user1 not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// break user1 public key
		$this->view->rename(
			\OCA\Files_Encryption\Keymanager::getPublicKeyPath() . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.publicKey_backup',
			\OCA\Files_Encryption\Keymanager::getPublicKeyPath() . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.publicKey');

		// remove share file
		$this->view->unlink('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3
			. '.shareKey');

		// re-enable the file proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		// unshare the file with user1
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, self::TEST_ENCRYPTION_SHARE_GROUP1);

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));

		// cleanup
		$this->view->chroot('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/');
		$this->view->unlink($this->filename);
		$this->view->chroot('/');
	}


	/**
	 * test rename a shared file mount point
	 */
	function testRename() {

		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2, \OCP\Constants::PERMISSION_ALL);

		// check if share key for user1 and user2 exists
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));


		// login as user2
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER2);

		$this->assertTrue($this->view->file_exists('/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename));

		// get file contents
		$retrievedCryptedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		\OC\Files\Filesystem::mkdir($this->folder1);

		// move the file to a subfolder
		\OC\Files\Filesystem::rename($this->filename, $this->folder1 . $this->filename);

		// check if we can read the moved file
		$retrievedRenamedFile = $this->view->file_get_contents(
			'/' . self::TEST_ENCRYPTION_SHARE_USER2 . '/files/' . $this->folder1 .  $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedRenamedFile);

		// check if share key for user2 and user1 still exists
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// cleanup
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);
		$this->view->unlink('/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);
	}

	function testRenameGroupShare() {
		// login as admin
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename, $this->dataShort);

		// test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// get the file info from previous created file
		$fileInfo = $this->view->getFileInfo(
			'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files/' . $this->filename);

		// check if we have a valid file info
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// share the file
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_GROUP, self::TEST_ENCRYPTION_SHARE_GROUP1, \OCP\Constants::PERMISSION_ALL);

		// check if share key for user1, user3 and user4 exists
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));


		// login as user2
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER3);

		$this->assertTrue(\OC\Files\Filesystem::file_exists($this->filename));

		// get file contents
		$retrievedCryptedFile = \OC\Files\Filesystem::file_get_contents($this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedCryptedFile);

		\OC\Files\Filesystem::mkdir($this->folder1);

		// move the file to a subfolder
		\OC\Files\Filesystem::rename($this->filename, $this->folder1 . $this->filename);

		// check if we can read the moved file
		$retrievedRenamedFile = \OC\Files\Filesystem::file_get_contents($this->folder1 . $this->filename);

		// check if data is the same as we previously written
		$this->assertEquals($this->dataShort, $retrievedRenamedFile);

		// check if share key for user1, user3 and user4 still exists
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER3 . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/' . self::TEST_ENCRYPTION_SHARE_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_SHARE_USER4 . '.shareKey'));

		// cleanup
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);
		\OC\Files\Filesystem::unlink($this->filename);
	}

	/**
	 * test if additional share keys are added if we move a folder to a shared parent
	 * @medium
	 */
	function testMoveFolder() {

		$view = new \OC\Files\View('/' . self::TEST_ENCRYPTION_SHARE_USER1);

		$filename = '/tmp-' . $this->getUniqueID();
		$folder = '/folder' . $this->getUniqueID();

		\OC\Files\Filesystem::mkdir($folder);

		// Save long data as encrypted file using stream wrapper
		$cryptedFile = \OC\Files\Filesystem::file_put_contents($folder . $filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// Get file decrypted contents
		$decrypt = \OC\Files\Filesystem::file_get_contents($folder . $filename);

		$this->assertEquals($this->dataShort, $decrypt);

		$newFolder = '/newfolder/subfolder' . $this->getUniqueID();
		\OC\Files\Filesystem::mkdir('/newfolder');

		// get the file info from previous created file
		$fileInfo = \OC\Files\Filesystem::getFileInfo('/newfolder');
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// share the folder
		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2, \OCP\Constants::PERMISSION_ALL);

		\OC\Files\Filesystem::rename($folder, $newFolder);

		// Get file decrypted contents
		$newDecrypt = \OC\Files\Filesystem::file_get_contents($newFolder . $filename);
		$this->assertEquals($this->dataShort, $newDecrypt);

		// check if additional share key for user2 exists
		$this->assertTrue($view->file_exists('files_encryption/keys' . $newFolder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// check that old keys were removed/moved properly
		$this->assertFalse($view->file_exists('files_encryption/keys' . $folder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// tear down
		\OC\Files\Filesystem::unlink($newFolder);
		\OC\Files\Filesystem::unlink('/newfolder');
	}

	function usersProvider() {
		return array(
			// test as owner
			array(self::TEST_ENCRYPTION_SHARE_USER1),
			// test as share receiver
			array(self::TEST_ENCRYPTION_SHARE_USER2),
		);
	}

	/**
	 * @dataProvider usersProvider
	 */
	function testMoveFileToFolder($userId) {
		$view = new \OC\Files\View('/' . self::TEST_ENCRYPTION_SHARE_USER1);

		$filename = '/tmp-' . $this->getUniqueID();
		$folder = '/folder' . $this->getUniqueID();

		\OC\Files\Filesystem::mkdir($folder);

		// Save long data as encrypted file using stream wrapper
		$cryptedFile = \OC\Files\Filesystem::file_put_contents($folder . $filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertInternalType('int', $cryptedFile);

		// Get file decrypted contents
		$decrypt = \OC\Files\Filesystem::file_get_contents($folder . $filename);

		$this->assertEquals($this->dataShort, $decrypt);

		$subFolder = $folder . '/subfolder' . $this->getUniqueID();
		\OC\Files\Filesystem::mkdir($subFolder);

		// get the file info from previous created file
		$fileInfo = \OC\Files\Filesystem::getFileInfo($folder);
		$this->assertInstanceOf('\OC\Files\FileInfo', $fileInfo);

		// share the folder
		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_SHARE_USER2, \OCP\Constants::PERMISSION_ALL);

		// check that the share keys exist
		$this->assertTrue($view->file_exists('files_encryption/keys' . $folder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($view->file_exists('files_encryption/keys' . $folder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// move the file into the subfolder as the test user
		self::loginHelper($userId);
		\OC\Files\Filesystem::rename($folder . $filename, $subFolder . $filename);
		self::loginHelper(self::TEST_ENCRYPTION_SHARE_USER1);

		// Get file decrypted contents
		$newDecrypt = \OC\Files\Filesystem::file_get_contents($subFolder . $filename);
		$this->assertEquals($this->dataShort, $newDecrypt);

		// check if additional share key for user2 exists
		$this->assertTrue($view->file_exists('files_encryption/keys' . $subFolder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertTrue($view->file_exists('files_encryption/keys' . $subFolder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// check that old keys were removed/moved properly
		$this->assertFalse($view->file_exists('files_encryption/keys' . $folder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER1 . '.shareKey'));
		$this->assertFalse($view->file_exists('files_encryption/keys' . $folder . '/' . $filename . '/' . self::TEST_ENCRYPTION_SHARE_USER2 . '.shareKey'));

		// tear down
		\OC\Files\Filesystem::unlink($subFolder);
		\OC\Files\Filesystem::unlink($folder);
	}

}
