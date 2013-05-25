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

require_once realpath(dirname(__FILE__) . '/../../../lib/base.php');
require_once realpath(dirname(__FILE__) . '/../lib/crypt.php');
require_once realpath(dirname(__FILE__) . '/../lib/keymanager.php');
require_once realpath(dirname(__FILE__) . '/../lib/proxy.php');
require_once realpath(dirname(__FILE__) . '/../lib/stream.php');
require_once realpath(dirname(__FILE__) . '/../lib/util.php');
require_once realpath(dirname(__FILE__) . '/../appinfo/app.php');
require_once realpath(dirname(__FILE__) . '/../../files_trashbin/appinfo/app.php');

use OCA\Encryption;

/**
 * Class Test_Encryption_Trashbin
 * @brief this class provide basic trashbin app tests
 */
class Test_Encryption_Trashbin extends \PHPUnit_Framework_TestCase
{

	public $userId;
	public $pass;
	/**
	 * @var \OC_FilesystemView
	 */
	public $view;
	public $dataShort;
	public $stateFilesTrashbin;
	public $folder1;
	public $subfolder;
	public $subsubfolder;

	function setUp()
	{
		// reset backend
		\OC_User::useBackend('database');

		// set user id
		\OC_User::setUserId('admin');
		$this->userId = 'admin';
		$this->pass = 'admin';

		// init filesystem view
		$this->view = new \OC_FilesystemView('/');

		// init short data
		$this->dataShort = 'hats';

		$this->folder1 = '/folder1';
		$this->subfolder = '/subfolder1';
		$this->subsubfolder = '/subsubfolder1';

		\OC_Hook::clear('OC_Filesystem');
		\OC_Hook::clear('OC_User');

		// init filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// register encryption file proxy
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// trashbin hooks
		\OCA\Files_Trashbin\Trashbin::registerHooks();

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::enable('files_trashbin');

		// init filesystem for user
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_Util::setupFS($this->userId);
		\OC_User::setUserId($this->userId);

		// login user
		$params['uid'] = $this->userId;
		$params['password'] = $this->pass;
		OCA\Encryption\Hooks::login($params);
	}

	function tearDown()
	{
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		} else {
			OC_App::disable('files_trashbin');
		}

		// clear all proxies
		\OC_FileProxy::clearProxies();
	}

	/**
	 * @brief test delete file
	 */
	function testDeleteFile() {

		// generate filename
		$filename = 'tmp-' . time() . '.txt';

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . $filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/keyfiles/' . $filename . '.key'));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $filename . '.admin.shareKey'));

		// delete file
		\OC\FIles\Filesystem::unlink($filename);

		// check if file not exists
		$this->assertFalse($this->view->file_exists('/admin/files/' . $filename));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/keyfiles/' . $filename . '.key'));

		// check if share key for admin not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $filename . '.admin.shareKey'));

		// get files
		$trashFiles = $this->view->getDirectoryContent('/admin/files_trashbin/files/');

		$trashFileSuffix = null;
		// find created file with timestamp
		foreach($trashFiles as $file) {
			if(strncmp($file['path'], $filename, strlen($filename))) {
				$path_parts = pathinfo($file['name']);
				$trashFileSuffix = $path_parts['extension'];
			}
		}

		// check if we found the file we created
		$this->assertNotNull($trashFileSuffix);

		// check if key for admin not exists
		$this->assertTrue($this->view->file_exists('/admin/files_trashbin/keyfiles/' . $filename . '.key.' . $trashFileSuffix));

		// check if share key for admin not exists
		$this->assertTrue($this->view->file_exists('/admin/files_trashbin/share-keys/' . $filename . '.admin.shareKey.' . $trashFileSuffix));

		// return filename for next test
		return $filename . '.' . $trashFileSuffix;
	}

	/**
	 * @brief test restore file
	 *
	 * @depends testDeleteFile
	 */
	function testRestoreFile($filename) {

		// prepare file information
		$path_parts = pathinfo($filename);
		$trashFileSuffix = $path_parts['extension'];
		$timestamp = str_replace('d', '', $trashFileSuffix);
		$fileNameWithoutSuffix = str_replace('.'.$trashFileSuffix, '', $filename);

		// restore file
		$this->assertTrue(\OCA\Files_Trashbin\Trashbin::restore($filename, $fileNameWithoutSuffix, $timestamp));

		// check if file exists
		$this->assertTrue($this->view->file_exists('/admin/files/' . $fileNameWithoutSuffix));

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/keyfiles/' . $fileNameWithoutSuffix . '.key'));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $fileNameWithoutSuffix . '.admin.shareKey'));
	}

	/**
	 * @brief test delete file forever
	 */
	function testPermanentDeleteFile() {

		// generate filename
		$filename = 'tmp-' . time() . '.txt';

		// save file with content
		$cryptedFile = file_put_contents('crypt:///' . $filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/keyfiles/' . $filename . '.key'));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_encryption/share-keys/' . $filename . '.admin.shareKey'));

		// delete file
		\OC\FIles\Filesystem::unlink($filename);

		// check if file not exists
		$this->assertFalse($this->view->file_exists('/admin/files/' . $filename));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/keyfiles/' . $filename . '.key'));

		// check if share key for admin not exists
		$this->assertFalse($this->view->file_exists('/admin/files_encryption/share-keys/' . $filename . '.admin.shareKey'));

		// get files
		$trashFiles = $this->view->getDirectoryContent('/admin/files_trashbin/files/');

		$trashFileSuffix = null;
		// find created file with timestamp
		foreach($trashFiles as $file) {
			if(strncmp($file['name'], $filename, strlen($filename)) == 0) {
				$path_parts = pathinfo($file['name']);
				$trashFileSuffix = $path_parts['extension'];
				break;
			}
		}

		// check if we found the file we created
		$this->assertNotNull($trashFileSuffix);

		// check if key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_trashbin/keyfiles/' . $filename . '.key.' . $trashFileSuffix));

		// check if share key for admin exists
		$this->assertTrue($this->view->file_exists('/admin/files_trashbin/share-keys/' . $filename . '.admin.shareKey.' . $trashFileSuffix));

		// get timestamp from file
		$timestamp = str_replace('d', '', $trashFileSuffix);

		// delete file forever
		$this->assertGreaterThan(0, \OCA\Files_Trashbin\Trashbin::delete($filename, $timestamp));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists('/admin/files_trashbin/files/' . $filename . '.' . $trashFileSuffix));

		// check if key for admin not exists
		$this->assertFalse($this->view->file_exists('/admin/files_trashbin/keyfiles/' . $filename . '.key.' . $trashFileSuffix));

		// check if share key for admin not exists
		$this->assertFalse($this->view->file_exists('/admin/files_trashbin/share-keys/' . $filename . '.admin.shareKey.' . $trashFileSuffix));
	}

}