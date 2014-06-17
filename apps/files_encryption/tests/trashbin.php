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

require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/../lib/crypt.php';
require_once __DIR__ . '/../lib/keymanager.php';
require_once __DIR__ . '/../lib/proxy.php';
require_once __DIR__ . '/../lib/stream.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../appinfo/app.php';
require_once __DIR__ . '/../../files_trashbin/appinfo/app.php';
require_once __DIR__ . '/util.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Trashbin
 * this class provide basic trashbin app tests
 */
class Test_Encryption_Trashbin extends \PHPUnit_Framework_TestCase {

	const TEST_ENCRYPTION_TRASHBIN_USER1 = "test-trashbin-user1";

	public $userId;
	public $pass;
	/**
	 * @var \OC\Files\View
	 */
	public $view;
	public $dataShort;
	public $stateFilesTrashbin;
	public $folder1;
	public $subfolder;
	public $subsubfolder;

	public static function setUpBeforeClass() {
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		\OC_Hook::clear('OC_Filesystem');
		\OC_Hook::clear('OC_User');

		// trashbin hooks
		\OCA\Files_Trashbin\Trashbin::registerHooks();

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// create test user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1, true);
	}

	function setUp() {
		// set user id
		\OC_User::setUserId(\Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1);
		$this->userId = \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1;
		$this->pass = \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1;

		// init filesystem view
		$this->view = new \OC\Files\View('/');

		// init short data
		$this->dataShort = 'hats';

		$this->folder1 = '/folder1';
		$this->subfolder = '/subfolder1';
		$this->subsubfolder = '/subsubfolder1';

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we want to tests with app files_trashbin enabled
		\OC_App::enable('files_trashbin');
	}

	function tearDown() {
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		}
		else {
			OC_App::disable('files_trashbin');
		}
	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(\Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1);
	}

	/**
	 * @medium
	 * test delete file
	 */
	function testDeleteFile() {

		// generate filename
		$filename = 'tmp-' . uniqid() . '.txt';

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' .\Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1. '/files/'. $filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/keyfiles/' . $filename
			. '.key'));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey'));

		// delete file
		\OC\FIles\Filesystem::unlink($filename);

		// check if file not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files/' . $filename));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/keyfiles/' . $filename
			. '.key'));

		// check if share key for admin not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey'));

		// get files
		$trashFiles = $this->view->getDirectoryContent(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/files/');

		$trashFileSuffix = null;
		// find created file with timestamp
		foreach ($trashFiles as $file) {
			if (strncmp($file['path'], $filename, strlen($filename))) {
				$path_parts = pathinfo($file['name']);
				$trashFileSuffix = $path_parts['extension'];
			}
		}

		// check if we found the file we created
		$this->assertNotNull($trashFileSuffix);

		// check if key for admin not exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/keyfiles/' . $filename
			. '.key.' . $trashFileSuffix));

		// check if share key for admin not exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/share-keys/' . $filename
			. '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey.' . $trashFileSuffix));

		// return filename for next test
		return $filename . '.' . $trashFileSuffix;
	}

	/**
	 * @medium
	 * test restore file
	 *
	 * @depends testDeleteFile
	 */
	function testRestoreFile($filename) {

		// prepare file information
		$path_parts = pathinfo($filename);
		$trashFileSuffix = $path_parts['extension'];
		$timestamp = str_replace('d', '', $trashFileSuffix);
		$fileNameWithoutSuffix = str_replace('.' . $trashFileSuffix, '', $filename);

		// restore file
		$this->assertTrue(\OCA\Files_Trashbin\Trashbin::restore($filename, $fileNameWithoutSuffix, $timestamp));

		// check if file exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files/' . $fileNameWithoutSuffix));

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/keyfiles/'
			. $fileNameWithoutSuffix . '.key'));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/share-keys/'
			. $fileNameWithoutSuffix . '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey'));
	}

	/**
	 * @medium
	 * test delete file forever
	 */
	function testPermanentDeleteFile() {

		// generate filename
		$filename = 'tmp-' . uniqid() . '.txt';

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' .$this->userId. '/files/' . $filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/keyfiles/' . $filename
			. '.key'));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey'));

		// delete file
		\OC\FIles\Filesystem::unlink($filename);

		// check if file not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files/' . $filename));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/keyfiles/' . $filename
			. '.key'));

		// check if share key for admin not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_encryption/share-keys/'
			. $filename . '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey'));

		// find created file with timestamp
		$query = \OC_DB::prepare('SELECT `timestamp`,`type` FROM `*PREFIX*files_trash`'
								 . ' WHERE `id`=?');
		$result = $query->execute(array($filename))->fetchRow();

		$this->assertTrue(is_array($result));

		// build suffix
		$trashFileSuffix = 'd' . $result['timestamp'];

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/keyfiles/' . $filename
			. '.key.' . $trashFileSuffix));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/share-keys/' . $filename
			. '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey.' . $trashFileSuffix));

		// get timestamp from file
		$timestamp = str_replace('d', '', $trashFileSuffix);

		// delete file forever
		$this->assertGreaterThan(0, \OCA\Files_Trashbin\Trashbin::delete($filename, $this->userId, $timestamp));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/files/' . $filename . '.'
			. $trashFileSuffix));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/keyfiles/' . $filename
			. '.key.' . $trashFileSuffix));

		// check if share key for admin not exists
		$this->assertFalse($this->view->file_exists(
			'/' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '/files_trashbin/share-keys/' . $filename
			. '.' . \Test_Encryption_Trashbin::TEST_ENCRYPTION_TRASHBIN_USER1 . '.shareKey.' . $trashFileSuffix));
	}

}
