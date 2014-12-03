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

namespace OCA\Files_Encryption\Tests;

/**
 * Class Hooks
 * this class provide basic hook app tests
 */
class Hooks extends TestCase {

	const TEST_ENCRYPTION_HOOKS_USER1 = "test-encryption-hooks-user1.dot";
	const TEST_ENCRYPTION_HOOKS_USER2 = "test-encryption-hooks-user2.dot";

	/** @var \OC\Files\View */
	public $user1View;     // view on /data/user1/files
	/** @var \OC\Files\View */
	public $user2View;     // view on /data/user2/files
	/** @var \OC\Files\View */
	public $rootView; // view on /data/user
	public $data;
	public $filename;
	public $folder;

	private static $testFiles;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// note: not using a data provider because these
		// files all need to coexist to make sure the
		// share keys are found properly (pattern matching)
		self::$testFiles = array(
			't est.txt',
			't est_.txt',
			't est.doc.txt',
			't est(.*).txt', // make sure the regexp is escaped
			'multiple.dots.can.happen.too.txt',
			't est.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.txt',
			't est_.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey.txt',
			'who would upload their.shareKey',
			'user ones file.txt',
			'user ones file.txt.backup',
			'.t est.txt'
		);

		// create test user
		self::loginHelper(self::TEST_ENCRYPTION_HOOKS_USER1, true);
		self::loginHelper(self::TEST_ENCRYPTION_HOOKS_USER2, true);
	}

	protected function setUp() {
		parent::setUp();

		// set user id
		self::loginHelper(self::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::setUserId(self::TEST_ENCRYPTION_HOOKS_USER1);

		// init filesystem view
		$this->user1View = new \OC\Files\View('/'. self::TEST_ENCRYPTION_HOOKS_USER1 . '/files');
		$this->user2View = new \OC\Files\View('/'. self::TEST_ENCRYPTION_HOOKS_USER2 . '/files');
		$this->rootView = new \OC\Files\View('/');

		// init short data
		$this->data = 'hats';
		$this->filename = 'enc_hooks_tests-' . $this->getUniqueID() . '.txt';
		$this->folder = 'enc_hooks_tests_folder-' . $this->getUniqueID();

	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_HOOKS_USER2);

		parent::tearDownAfterClass();
	}

	function testDisableHook() {
		// encryption is enabled and running so we should have some user specific
		// settings in oc_preferences
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*preferences` WHERE `appid` = ?');
		$result = $query->execute(array('files_encryption'));
		$row = $result->fetchRow();
		$this->assertTrue(is_array($row));

		// disabling the app should delete all user specific settings
		\OCA\Files_Encryption\Hooks::preDisable(array('app' => 'files_encryption'));

		// check if user specific settings for the encryption app are really gone
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*preferences` WHERE `appid` = ?');
		$result = $query->execute(array('files_encryption'));
		$row = $result->fetchRow();
		$this->assertFalse($row);

		// relogin user to initialize the encryption again
		$user =  \OCP\User::getUser();
		self::loginHelper($user);

	}

	function testDeleteHooks() {

		// remember files_trashbin state
		$stateFilesTrashbin = \OC_App::isEnabled('files_trashbin');

		// we want to tests with app files_trashbin disabled
		\OC_App::disable('files_trashbin');

		// make sure that the trash bin is disabled
		$this->assertFalse(\OC_APP::isEnabled('files_trashbin'));

		$this->user1View->file_put_contents($this->filename, $this->data);

		// check if all keys are generated
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->filename . '/fileKey'));


		self::logoutHelper();
		self::loginHelper(self::TEST_ENCRYPTION_HOOKS_USER2);
		\OC_User::setUserId(self::TEST_ENCRYPTION_HOOKS_USER2);


		$this->user2View->file_put_contents($this->filename, $this->data);

		// check if all keys are generated
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/' . $this->filename . '/fileKey'));


		// create a dummy file that we can delete something outside of data/user/files
		// in this case no share or file keys should be deleted
		$this->rootView->file_put_contents(self::TEST_ENCRYPTION_HOOKS_USER2 . "/" . $this->filename, $this->data);

		// delete dummy file outside of data/user/files
		$this->rootView->unlink(self::TEST_ENCRYPTION_HOOKS_USER2 . "/" . $this->filename);

		// all keys should still exist
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/' . $this->filename . '/fileKey'));


		// delete the file in data/user/files
		// now the correspondig share and file keys from user2 should be deleted
		$this->user2View->unlink($this->filename);

		// check if keys from user2 are really deleted
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/' . $this->filename . '/fileKey'));

		// but user1 keys should still exist
		$this->assertTrue($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->filename . '/fileKey'));

		if ($stateFilesTrashbin) {
			\OC_App::enable('files_trashbin');
		}
		else {
			\OC_App::disable('files_trashbin');
		}
	}

	function testDeleteHooksForSharedFiles() {

		self::logoutHelper();
		self::loginHelper(self::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::setUserId(self::TEST_ENCRYPTION_HOOKS_USER1);

		// remember files_trashbin state
		$stateFilesTrashbin = \OC_App::isEnabled('files_trashbin');

		// we want to tests with app files_trashbin disabled
		\OC_App::disable('files_trashbin');

		// make sure that the trash bin is disabled
		$this->assertFalse(\OC_APP::isEnabled('files_trashbin'));

		$this->user1View->file_put_contents($this->filename, $this->data);

		// check if all keys are generated
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->filename . '/fileKey'));

		// get the file info from previous created file
		$fileInfo = $this->user1View->getFileInfo($this->filename);

		// check if we have a valid file info
		$this->assertTrue($fileInfo instanceof \OC\Files\FileInfo);

		// share the file with user2
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_HOOKS_USER2, \OCP\Constants::PERMISSION_ALL);

		// check if new share key exists
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));

		self::logoutHelper();
		self::loginHelper(self::TEST_ENCRYPTION_HOOKS_USER2);
		\OC_User::setUserId(self::TEST_ENCRYPTION_HOOKS_USER2);

		// user2 update the shared file
		$this->user2View->file_put_contents($this->filename, $this->data);

		// keys should be stored at user1s dir, not in user2s
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keys/' . $this->filename . '/fileKey'));

		// delete the Shared file from user1 in data/user2/files/Shared
		$result = $this->user2View->unlink($this->filename);

		$this->assertTrue($result);

		// share key for user2 from user1s home should be gone, all other keys should still exists
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertFalse($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
				. $this->filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->filename . '/fileKey'));

		// cleanup

		self::logoutHelper();
		self::loginHelper(self::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::setUserId(self::TEST_ENCRYPTION_HOOKS_USER1);

		if ($stateFilesTrashbin) {
			\OC_App::enable('files_trashbin');
		}
		else {
			\OC_App::disable('files_trashbin');
		}
	}

	function testRenameHook() {
		// create all files to make sure all keys can coexist properly
		foreach (self::$testFiles as $file) {
			// save file with content
			$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $file, $this->data);

			// test that data was successfully written
			$this->assertTrue(is_int($cryptedFile));
		}

		foreach (self::$testFiles as $file) {
			$this->doTestRenameHook($file);
		}
	}

	/**
	 * test rename operation
	 */
	function doTestRenameHook($filename) {
		// check if keys exists
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/fileKey'));

		// make subfolder and sub-subfolder
		$this->rootView->mkdir('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder);
		$this->rootView->mkdir('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder . '/' . $this->folder);

		$this->assertTrue($this->rootView->is_dir('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder . '/' . $this->folder));

		// move the file to the sub-subfolder
		$root = $this->rootView->getRoot();
		$this->rootView->chroot('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/');
		$this->rootView->rename($filename, '/' . $this->folder . '/' . $this->folder . '/' . $filename);
		$this->rootView->chroot($root);

		$this->assertFalse($this->rootView->file_exists('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $filename));
		$this->assertTrue($this->rootView->file_exists('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder . '/' . $this->folder . '/' . $filename));

		// keys should be renamed too
		$this->assertFalse($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertFalse($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/fileKey'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '/fileKey'));

		// cleanup
		$this->rootView->unlink('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder);
	}

	function testCopyHook() {
		// create all files to make sure all keys can coexist properly
		foreach (self::$testFiles as $file) {
			// save file with content
			$cryptedFile = file_put_contents('crypt:///' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $file, $this->data);

			// test that data was successfully written
			$this->assertTrue(is_int($cryptedFile));
		}

		foreach (self::$testFiles as $file) {
			$this->doTestCopyHook($file);
		}
	}

	/**
	 * test rename operation
	 */
	function doTestCopyHook($filename) {
		// check if keys exists
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/fileKey'));

		// make subfolder and sub-subfolder
		$this->rootView->mkdir('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder);
		$this->rootView->mkdir('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder . '/' . $this->folder);

		$this->assertTrue($this->rootView->is_dir('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder . '/' . $this->folder));

		// copy the file to the sub-subfolder
		\OC\Files\Filesystem::copy($filename, '/' . $this->folder . '/' . $this->folder . '/' . $filename);

		$this->assertTrue($this->rootView->file_exists('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $filename));
		$this->assertTrue($this->rootView->file_exists('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder . '/' . $this->folder . '/' . $filename));

		// keys should be copied too
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/'
			. $filename . '/fileKey'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keys/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '/fileKey'));

		// cleanup
		$this->rootView->unlink('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $this->folder);
		$this->rootView->unlink('/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files/' . $filename);
	}

	/**
	 * @brief replacing encryption keys during password change should be allowed
	 *        until the user logged in for the first time
	 */
	public function testSetPassphrase() {

		$view = new \OC\Files\View();

		// set user password for the first time
		\OCA\Files_Encryption\Hooks::postCreateUser(array('uid' => 'newUser', 'password' => 'newUserPassword'));

		$this->assertTrue($view->file_exists(\OCA\Files_Encryption\Keymanager::getPublicKeyPath() . '/newUser.publicKey'));
		$this->assertTrue($view->file_exists('newUser/files_encryption/newUser.privateKey'));

		// check if we are able to decrypt the private key
		$encryptedKey = \OCA\Files_Encryption\Keymanager::getPrivateKey($view, 'newUser');
		$privateKey = \OCA\Files_Encryption\Crypt::decryptPrivateKey($encryptedKey, 'newUserPassword');
		$this->assertTrue(is_string($privateKey));

		// change the password before the user logged-in for the first time,
		// we can replace the encryption keys
		\OCA\Files_Encryption\Hooks::setPassphrase(array('uid' => 'newUser', 'password' => 'passwordChanged'));

		$encryptedKey = \OCA\Files_Encryption\Keymanager::getPrivateKey($view, 'newUser');
		$privateKey = \OCA\Files_Encryption\Crypt::decryptPrivateKey($encryptedKey, 'passwordChanged');
		$this->assertTrue(is_string($privateKey));

		// now create a files folder to simulate a already used account
		$view->mkdir('/newUser/files');

		// change the password after the user logged in, now the password should not change
		\OCA\Files_Encryption\Hooks::setPassphrase(array('uid' => 'newUser', 'password' => 'passwordChanged2'));

		$encryptedKey = \OCA\Files_Encryption\Keymanager::getPrivateKey($view, 'newUser');
		$privateKey = \OCA\Files_Encryption\Crypt::decryptPrivateKey($encryptedKey, 'passwordChanged2');
		$this->assertFalse($privateKey);

		$privateKey = \OCA\Files_Encryption\Crypt::decryptPrivateKey($encryptedKey, 'passwordChanged');
		$this->assertTrue(is_string($privateKey));

	}

}
