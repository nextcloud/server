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
require_once realpath(dirname(__FILE__) . '/util.php');

use OCA\Encryption;

/**
 * Class Test_Encryption_Stream
 * @brief this class provide basic stream tests
 */
class Test_Encryption_Stream extends \PHPUnit_Framework_TestCase {

	const TEST_ENCRYPTION_STREAM_USER1 = "test-stream-user1";

	public $userId;
	public $pass;
	/**
	 * @var \OC_FilesystemView
	 */
	public $view;
	public $dataShort;
	public $stateFilesTrashbin;

	public static function setUpBeforeClass() {
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// create test user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Stream::TEST_ENCRYPTION_STREAM_USER1, true);
	}

	function setUp() {
		// set user id
		\OC_User::setUserId(\Test_Encryption_Stream::TEST_ENCRYPTION_STREAM_USER1);
		$this->userId = \Test_Encryption_Stream::TEST_ENCRYPTION_STREAM_USER1;
		$this->pass = \Test_Encryption_Stream::TEST_ENCRYPTION_STREAM_USER1;

		// init filesystem view
		$this->view = new \OC_FilesystemView('/');

		// init short data
		$this->dataShort = 'hats';

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');
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
		\OC_User::deleteUser(\Test_Encryption_Stream::TEST_ENCRYPTION_STREAM_USER1);
	}

	function testStreamOptions() {
		$filename = '/tmp-' . time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		$handle = $view->fopen($filename, 'r');

		// check if stream is at position zero
		$this->assertEquals(0, ftell($handle));

		// set stream options
		$this->assertTrue(flock($handle, LOCK_SH));
		$this->assertTrue(flock($handle, LOCK_UN));

		// tear down
		$view->unlink($filename);
	}

	function testStreamSetBlocking() {
		$filename = '/tmp-' . time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		$handle = $view->fopen($filename, 'r');

		// set stream options
		$this->assertTrue(stream_set_blocking($handle, 1));

		// tear down
		$view->unlink($filename);
	}

	/**
	 * @medium
	 */
	function testStreamSetTimeout() {
		$filename = '/tmp-' . time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		$handle = $view->fopen($filename, 'r');

		// set stream options
		$this->assertFalse(stream_set_timeout($handle, 1));

		// tear down
		$view->unlink($filename);
	}

	function testStreamSetWriteBuffer() {
		$filename = '/tmp-' . time();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		$handle = $view->fopen($filename, 'r');

		// set stream options
		$this->assertEquals(0, stream_set_write_buffer($handle, 1024));

		// tear down
		$view->unlink($filename);
	}
}