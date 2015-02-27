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
 * Class Stream
 * this class provide basic stream tests
 */
class Stream extends TestCase {

	const TEST_ENCRYPTION_STREAM_USER1 = "test-stream-user1";

	public $userId;
	public $pass;
	/**
	 * @var \OC\Files\View
	 */
	public $view;
	public $dataShort;
	public $stateFilesTrashbin;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// create test user
		self::loginHelper(self::TEST_ENCRYPTION_STREAM_USER1, true);
	}

	protected function setUp() {
		parent::setUp();

		// set user id
		\OC_User::setUserId(self::TEST_ENCRYPTION_STREAM_USER1);
		$this->userId = self::TEST_ENCRYPTION_STREAM_USER1;
		$this->pass = self::TEST_ENCRYPTION_STREAM_USER1;

		// init filesystem view
		$this->view = new \OC\Files\View('/');

		// init short data
		$this->dataShort = 'hats';

		// remember files_trashbin state
		$this->stateFilesTrashbin = \OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');
	}

	protected function tearDown() {
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			\OC_App::enable('files_trashbin');
		}
		else {
			\OC_App::disable('files_trashbin');
		}

		parent::tearDown();
	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_ENCRYPTION_STREAM_USER1);

		parent::tearDownAfterClass();
	}

	function testStreamOptions() {
		$filename = '/tmp-' . $this->getUniqueID();
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

		fclose($handle);

		// tear down
		$view->unlink($filename);
	}

	function testStreamSetBlocking() {
		$filename = '/tmp-' . $this->getUniqueID();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		$handle = $view->fopen($filename, 'r');


		if (\OC_Util::runningOnWindows()) {
			fclose($handle);
			$view->unlink($filename);
			$this->markTestSkipped('[Windows] stream_set_blocking() does not work as expected on Windows.');
		}

		// set stream options
		$this->assertTrue(stream_set_blocking($handle, 1));

		fclose($handle);

		// tear down
		$view->unlink($filename);
	}

	/**
	 * @medium
	 */
	function testStreamSetTimeout() {
		$filename = '/tmp-' . $this->getUniqueID();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		$handle = $view->fopen($filename, 'r');

		// set stream options
		$this->assertFalse(stream_set_timeout($handle, 1));

		fclose($handle);

		// tear down
		$view->unlink($filename);
	}

	function testStreamSetWriteBuffer() {
		$filename = '/tmp-' . $this->getUniqueID();
		$view = new \OC\Files\View('/' . $this->userId . '/files');

		// Save short data as encrypted file using stream wrapper
		$cryptedFile = $view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		$handle = $view->fopen($filename, 'r');

		// set stream options
		$this->assertEquals(0, stream_set_write_buffer($handle, 1024));

		fclose($handle);

		// tear down
		$view->unlink($filename);
	}

	/**
	 * @medium
	 * test if stream wrapper can read files outside from the data folder
	 */
	function testStreamFromLocalFile() {

		$filename = '/' . $this->userId . '/files/' . 'tmp-' . $this->getUniqueID().'.txt';

		$tmpFilename = "/tmp/" . $this->getUniqueID() . ".txt";

		// write an encrypted file
		$cryptedFile = $this->view->file_put_contents($filename, $this->dataShort);

		// Test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// create a copy outside of the data folder in /tmp
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;
		$encryptedContent = $this->view->file_get_contents($filename);
		\OC_FileProxy::$enabled = $proxyStatus;

		file_put_contents($tmpFilename, $encryptedContent);

		\OCA\Files_Encryption\Helper::addTmpFileToMapper($tmpFilename, $filename);

		// try to read the file from /tmp
		$handle = fopen("crypt://".$tmpFilename, "r");
		$contentFromTmpFile = stream_get_contents($handle);

		// check if it was successful
		$this->assertEquals($this->dataShort, $contentFromTmpFile);

		fclose($handle);

		// clean up
		unlink($tmpFilename);
		$this->view->unlink($filename);

	}
}
