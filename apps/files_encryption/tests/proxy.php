<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2013 Bjoern Schiessle <schiessle@owncloud.com>
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
 * Class Proxy
 * this class provide basic proxy app tests
 */
class Proxy extends TestCase {

	const TEST_ENCRYPTION_PROXY_USER1 = "test-proxy-user1";

	public $userId;
	public $pass;
	/**
	 * @var \OC\Files\View
	 */
	public $view;     // view in /data/user/files
	public $rootView; // view on /data/user
	public $data;
	public $dataLong;
	public $filename;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// create test user
		self::loginHelper(self::TEST_ENCRYPTION_PROXY_USER1, true);
	}

	protected function setUp() {
		parent::setUp();

		// set user id
		\OC_User::setUserId(self::TEST_ENCRYPTION_PROXY_USER1);
		$this->userId = self::TEST_ENCRYPTION_PROXY_USER1;
		$this->pass = self::TEST_ENCRYPTION_PROXY_USER1;

		// init filesystem view
		$this->view = new \OC\Files\View('/'. self::TEST_ENCRYPTION_PROXY_USER1 . '/files');
		$this->rootView = new \OC\Files\View('/'. self::TEST_ENCRYPTION_PROXY_USER1 );

		// init short data
		$this->data = 'hats';
		$this->dataLong = file_get_contents(__DIR__ . '/../lib/crypt.php');
		$this->filename = 'enc_proxy_tests-' . $this->getUniqueID() . '.txt';

	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_ENCRYPTION_PROXY_USER1);

		parent::tearDownAfterClass();
	}

	/**
	 * @medium
	 * test if postFileSize returns the unencrypted file size
	 */
	function testPostFileSize() {

		$this->view->file_put_contents($this->filename, $this->dataLong);
		$size = strlen($this->dataLong);

		\OC_FileProxy::$enabled = false;

		$encryptedSize = $this->view->filesize($this->filename);

		\OC_FileProxy::$enabled = true;

		$unencryptedSize = $this->view->filesize($this->filename);

		$this->assertTrue($encryptedSize > $unencryptedSize);
		$this->assertSame($size, $unencryptedSize);

		// cleanup
		$this->view->unlink($this->filename);

	}

	function testPostFileSizeWithDirectory() {

		$this->view->file_put_contents($this->filename, $this->data);

		\OC_FileProxy::$enabled = false;

		// get root size, must match the file's unencrypted size
		$unencryptedSize = $this->view->filesize('');

		\OC_FileProxy::$enabled = true;

		$encryptedSize = $this->view->filesize('');

		$this->assertTrue($encryptedSize !== $unencryptedSize);

		// cleanup
		$this->view->unlink($this->filename);

	}

	/**
	 * @dataProvider isExcludedPathProvider
	 */
	function testIsExcludedPath($path, $expected) {
		$this->view->mkdir(dirname($path));
		$this->view->file_put_contents($path, "test");

		$result = \Test_Helper::invokePrivate(new \OCA\Files_Encryption\Proxy(), 'isExcludedPath', array($path));
		$this->assertSame($expected, $result);

		$this->view->deleteAll(dirname($path));

	}

	public function isExcludedPathProvider() {
		return array(
			array ('/' . self::TEST_ENCRYPTION_PROXY_USER1 . '/files/test.txt', false),
			array (self::TEST_ENCRYPTION_PROXY_USER1 . '/files/test.txt', false),
			array ('/files/test.txt', true),
			array ('/' . self::TEST_ENCRYPTION_PROXY_USER1 . '/files/versions/test.txt', false),
			array ('/' . self::TEST_ENCRYPTION_PROXY_USER1 . '/files_versions/test.txt', false),
			array ('/' . self::TEST_ENCRYPTION_PROXY_USER1 . '/files_trashbin/test.txt', true),
			array ('/' . self::TEST_ENCRYPTION_PROXY_USER1 . '/file/test.txt', true),
		);
	}

}

