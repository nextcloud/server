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

require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/../lib/crypt.php';
require_once __DIR__ . '/../lib/keymanager.php';
require_once __DIR__ . '/../lib/stream.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../appinfo/app.php';
require_once __DIR__ . '/util.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Hooks
 * @brief this class provide basic hook app tests
 */
class Test_Encryption_Hooks extends \PHPUnit_Framework_TestCase {

	const TEST_ENCRYPTION_HOOKS_USER1 = "test-encryption-hooks-user1.dot";
	const TEST_ENCRYPTION_HOOKS_USER2 = "test-encryption-hooks-user2.dot";

	/**
	 * @var \OC_FilesystemView
	 */
	public $user1View;     // view on /data/user1/files
	public $user2View;     // view on /data/user2/files
	public $rootView; // view on /data/user
	public $data;
	public $filename;
	public $folder;

	private static $testFiles;

	public static function setUpBeforeClass() {
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

		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		\OC_Hook::clear('OC_Filesystem');
		\OC_Hook::clear('OC_User');

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks();
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// Sharing related hooks
		\OCA\Encryption\Helper::registerShareHooks();

		// clear and register proxies
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// create test user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1, true);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2, true);
	}

	function setUp() {
		// set user id
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::setUserId(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1);

		// init filesystem view
		$this->user1View = new \OC_FilesystemView('/'. \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1 . '/files');
		$this->user2View = new \OC_FilesystemView('/'. \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '/files');
		$this->rootView = new \OC_FilesystemView('/');

		// init short data
		$this->data = 'hats';
		$this->filename = 'enc_hooks_tests-' . uniqid() . '.txt';
		$this->folder = 'enc_hooks_tests_folder-' . uniqid();

	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::deleteUser(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2);
	}

	function testDeleteHooks() {

		// remember files_trashbin state
		$stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we want to tests with app files_trashbin disabled
		\OC_App::disable('files_trashbin');

		// make sure that the trash bin is disabled
		$this->assertFalse(\OC_APP::isEnabled('files_trashbin'));

		$this->user1View->file_put_contents($this->filename, $this->data);

		// check if all keys are generated
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/' . $this->filename . '.key'));


		\Test_Encryption_Util::logoutHelper();
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2);
		\OC_User::setUserId(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2);


		$this->user2View->file_put_contents($this->filename, $this->data);

		// check if all keys are generated
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keyfiles/' . $this->filename . '.key'));


		// create a dummy file that we can delete something outside of data/user/files
		// in this case no share or file keys should be deleted
		$this->rootView->file_put_contents(self::TEST_ENCRYPTION_HOOKS_USER2 . "/" . $this->filename, $this->data);

		// delete dummy file outside of data/user/files
		$this->rootView->unlink(self::TEST_ENCRYPTION_HOOKS_USER2 . "/" . $this->filename);

		// all keys should still exist
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keyfiles/' . $this->filename . '.key'));


		// delete the file in data/user/files
		// now the correspondig share and file keys from user2 should be deleted
		$this->user2View->unlink($this->filename);

		// check if keys from user2 are really deleted
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keyfiles/' . $this->filename . '.key'));

		// but user1 keys should still exist
		$this->assertTrue($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/' . $this->filename . '.key'));

		if ($stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		}
		else {
			OC_App::disable('files_trashbin');
		}
	}

	function testDeleteHooksForSharedFiles() {

		\Test_Encryption_Util::logoutHelper();
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::setUserId(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1);

		// remember files_trashbin state
		$stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we want to tests with app files_trashbin disabled
		\OC_App::disable('files_trashbin');

		// make sure that the trash bin is disabled
		$this->assertFalse(\OC_APP::isEnabled('files_trashbin'));

		$this->user1View->file_put_contents($this->filename, $this->data);

		// check if all keys are generated
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/' . $this->filename . '.key'));

		// get the file info from previous created file
		$fileInfo = $this->user1View->getFileInfo($this->filename);

		// check if we have a valid file info
		$this->assertTrue(is_array($fileInfo));

		// share the file with user2
		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_HOOKS_USER2, OCP\PERMISSION_ALL);

		// check if new share key exists
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));

		\Test_Encryption_Util::logoutHelper();
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2);
		\OC_User::setUserId(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2);

		// user2 has a local file with the same name
		$this->user2View->file_put_contents($this->filename, $this->data);

		// check if all keys are generated
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keyfiles/' . $this->filename . '.key'));

		// delete the Shared file from user1 in data/user2/files/Shared
		$this->user2View->unlink('/Shared/' . $this->filename);

		// now keys from user1s home should be gone
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		// FIXME: key is not properly removed
		/*
		$this->assertFalse($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		*/
		$this->assertFalse($this->rootView->file_exists(
			self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/' . $this->filename . '.key'));

		// but user2 keys should still exist
		$this->assertTrue($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/share-keys/'
				. $this->filename . '.' . \Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER2 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
				self::TEST_ENCRYPTION_HOOKS_USER2 . '/files_encryption/keyfiles/' . $this->filename . '.key'));

		// cleanup

		$this->user2View->unlink($this->filename);

		\Test_Encryption_Util::logoutHelper();
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1);
		\OC_User::setUserId(\Test_Encryption_Hooks::TEST_ENCRYPTION_HOOKS_USER1);

		// unshare the file
		\OCP\Share::unshare('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_ENCRYPTION_HOOKS_USER2);

		$this->user1View->unlink($this->filename);

		if ($stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		}
		else {
			OC_App::disable('files_trashbin');
		}
	}

	/**
	 * @brief test rename operation
	 */
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
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/'
			. $filename . '.key'));

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
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertFalse($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/'
			. $filename . '.key'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '.key'));

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
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/'
			. $filename . '.key'));

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
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/'
			. $filename . '.key'));

		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/share-keys/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '.' . self::TEST_ENCRYPTION_HOOKS_USER1 . '.shareKey'));
		$this->assertTrue($this->rootView->file_exists(
			'/' . self::TEST_ENCRYPTION_HOOKS_USER1 . '/files_encryption/keyfiles/' . $this->folder . '/' . $this->folder . '/'
			. $filename . '.key'));

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
		\OCA\Encryption\Hooks::postCreateUser(array('uid' => 'newUser', 'password' => 'newUserPassword'));

		$this->assertTrue($view->file_exists('public-keys/newUser.public.key'));
		$this->assertTrue($view->file_exists('newUser/files_encryption/newUser.private.key'));

		// check if we are able to decrypt the private key
		$encryptedKey = \OCA\Encryption\Keymanager::getPrivateKey($view, 'newUser');
		$privateKey = \OCA\Encryption\Crypt::decryptPrivateKey($encryptedKey, 'newUserPassword');
		$this->assertTrue(is_string($privateKey));

		// change the password before the user logged-in for the first time,
		// we can replace the encryption keys
		\OCA\Encryption\Hooks::setPassphrase(array('uid' => 'newUser', 'password' => 'passwordChanged'));

		$encryptedKey = \OCA\Encryption\Keymanager::getPrivateKey($view, 'newUser');
		$privateKey = \OCA\Encryption\Crypt::decryptPrivateKey($encryptedKey, 'passwordChanged');
		$this->assertTrue(is_string($privateKey));

		// now create a files folder to simulate a already used account
		$view->mkdir('/newUser/files');

		// change the password after the user logged in, now the password should not change
		\OCA\Encryption\Hooks::setPassphrase(array('uid' => 'newUser', 'password' => 'passwordChanged2'));

		$encryptedKey = \OCA\Encryption\Keymanager::getPrivateKey($view, 'newUser');
		$privateKey = \OCA\Encryption\Crypt::decryptPrivateKey($encryptedKey, 'passwordChanged2');
		$this->assertFalse($privateKey);

		$privateKey = \OCA\Encryption\Crypt::decryptPrivateKey($encryptedKey, 'passwordChanged');
		$this->assertTrue(is_string($privateKey));

	}

}
