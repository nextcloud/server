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

require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/../lib/crypt.php';
require_once __DIR__ . '/../lib/keymanager.php';
require_once __DIR__ . '/../lib/proxy.php';
require_once __DIR__ . '/../lib/stream.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../appinfo/app.php';
require_once __DIR__ . '/util.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Proxy
 * @brief this class provide basic proxy app tests
 */
class Test_Encryption_Proxy extends \PHPUnit_Framework_TestCase {

	const TEST_ENCRYPTION_PROXY_USER1 = "test-proxy-user1";

	public $userId;
	public $pass;
	/**
	 * @var \OC_FilesystemView
	 */
	public $view;
	public $data;

	public static function setUpBeforeClass() {
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		\OC_Hook::clear('OC_Filesystem');
		\OC_Hook::clear('OC_User');

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// create test user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Proxy::TEST_ENCRYPTION_PROXY_USER1, true);
	}

	function setUp() {
		// set user id
		\OC_User::setUserId(\Test_Encryption_Proxy::TEST_ENCRYPTION_PROXY_USER1);
		$this->userId = \Test_Encryption_Proxy::TEST_ENCRYPTION_PROXY_USER1;
		$this->pass = \Test_Encryption_Proxy::TEST_ENCRYPTION_PROXY_USER1;

		// init filesystem view
		$this->view = new \OC_FilesystemView('/'. \Test_Encryption_Proxy::TEST_ENCRYPTION_PROXY_USER1 . '/files');

		// init short data
		$this->data = 'hats';
	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(\Test_Encryption_Proxy::TEST_ENCRYPTION_PROXY_USER1);
	}

	/**
	 * @medium
	 * @brief test if postFileSize returns the unencrypted file size
	 */
	function testPostFileSize() {

		// generate filename
		$filename = 'tmp-' . time() . '.txt';

		$this->view->file_put_contents($filename, $this->data);

		\OC_FileProxy::$enabled = false;

		$unencryptedSize = $this->view->filesize($filename);

		\OC_FileProxy::$enabled = true;

		$encryptedSize = $this->view->filesize($filename);

		$this->assertTrue($encryptedSize !== $unencryptedSize);

	}

}
