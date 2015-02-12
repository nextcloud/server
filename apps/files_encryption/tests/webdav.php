<?php
/**
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Encryption\Tests;

/**
 * Class Webdav
 *
 * this class provide basic webdav tests for PUT,GET and DELETE
 */
class Webdav extends TestCase {

	const TEST_ENCRYPTION_WEBDAV_USER1 = "test-webdav-user1";

	public $userId;
	public $pass;
	/**
	 * @var \OC\Files\View
	 */
	public $view;
	public $dataShort;
	public $stateFilesTrashbin;

	private $storage;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// create test user
		self::loginHelper(self::TEST_ENCRYPTION_WEBDAV_USER1, true);

	}

	protected function setUp() {
		parent::setUp();

		// reset backend
		\OC_User::useBackend('database');

		// set user id
		\OC_User::setUserId(self::TEST_ENCRYPTION_WEBDAV_USER1);
		$this->userId = self::TEST_ENCRYPTION_WEBDAV_USER1;
		$this->pass = self::TEST_ENCRYPTION_WEBDAV_USER1;

		// init filesystem view
		$this->view = new \OC\Files\View('/');
		list($this->storage, ) = $this->view->resolvePath('/');
		// init short data
		$this->dataShort = 'hats';

		// remember files_trashbin state
		$this->stateFilesTrashbin = \OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

		// create test user
		self::loginHelper(self::TEST_ENCRYPTION_WEBDAV_USER1);
	}

	protected function tearDown() {
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			\OC_App::enable('files_trashbin');
		} else {
			\OC_App::disable('files_trashbin');
		}

		parent::tearDown();
	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_ENCRYPTION_WEBDAV_USER1);

		parent::tearDownAfterClass();
	}

	/**
	 * test webdav put random file
	 */
	function testWebdavPUT() {

		// generate filename
		$filename = '/tmp-' . $this->getUniqueID() . '.txt';

		// set server vars
		$_SERVER['REQUEST_METHOD'] = 'OPTIONS';

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['REQUEST_URI'] = '/remote.php/webdav' . $filename;
		$_SERVER['HTTP_AUTHORIZATION'] = 'Basic dGVzdC13ZWJkYXYtdXNlcjE6dGVzdC13ZWJkYXYtdXNlcjE=';
		$_SERVER['CONTENT_TYPE'] = 'application/octet-stream';
		$_SERVER['PATH_INFO'] = '/webdav' . $filename;
		$_SERVER['CONTENT_LENGTH'] = strlen($this->dataShort);

		// handle webdav request
		$this->handleWebdavRequest($this->dataShort);

		// check if file was created
		$this->assertTrue($this->view->file_exists('/' . $this->userId . '/files' . $filename));

		// check if key-file was created
		$this->assertTrue($this->view->file_exists(
			'/' . $this->userId . '/files_encryption/keys/' . $filename . '/fileKey'));

		// check if shareKey-file was created
		$this->assertTrue($this->view->file_exists(
			'/' . $this->userId . '/files_encryption/keys/' . $filename . '/' . $this->userId . '.shareKey'));

		// disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// get encrypted file content
		$encryptedContent = $this->view->file_get_contents('/' . $this->userId . '/files' . $filename);

		// restore proxy state
		\OC_FileProxy::$enabled = $proxyStatus;

		// check if encrypted content is valid
		$this->assertTrue(\OCA\Files_Encryption\Crypt::isCatfileContent($encryptedContent));

		// get decrypted file contents
		$decrypt = file_get_contents('crypt:///' . $this->userId . '/files' . $filename);

		// check if file content match with the written content
		$this->assertEquals($this->dataShort, $decrypt);

		// return filename for next test
		return $filename;
	}

	/**
	 * test webdav get random file
	 *
	 * @depends testWebdavPUT
	 */
	function testWebdavGET($filename) {

		// set server vars
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/remote.php/webdav' . $filename;
		$_SERVER['HTTP_AUTHORIZATION'] = 'Basic dGVzdC13ZWJkYXYtdXNlcjE6dGVzdC13ZWJkYXYtdXNlcjE=';
		$_SERVER['PATH_INFO'] = '/webdav' . $filename;

		// handle webdav request
		$content = $this->handleWebdavRequest();

		// check if file content match with the written content
		$this->assertEquals($this->dataShort, $content);

		// return filename for next test
		return $filename;
	}

	/**
	 * test webdav delete random file
	 * @depends testWebdavGET
	 */
	function testWebdavDELETE($filename) {
		// set server vars
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$_SERVER['REQUEST_URI'] = '/remote.php/webdav' . $filename;
		$_SERVER['HTTP_AUTHORIZATION'] = 'Basic dGVzdC13ZWJkYXYtdXNlcjE6dGVzdC13ZWJkYXYtdXNlcjE=';
		$_SERVER['PATH_INFO'] = '/webdav' . $filename;

		// at the beginning the file should exist
		$this->assertTrue($this->view->file_exists('/' . $this->userId . '/files' . $filename));

		// handle webdav request
		$content = $this->handleWebdavRequest();

		// check if file was removed
		$this->assertFalse($this->view->file_exists('/' . $this->userId . '/files' . $filename));

		// check if key-file was removed
		$this->assertFalse($this->view->file_exists(
			'/' . $this->userId . '/files_encryption/keys/' . $filename . '/fileKey'));

		// check if shareKey-file was removed
		$this->assertFalse($this->view->file_exists(
			'/' . $this->userId . '/files_encryption/keys/' . $filename . '/' . $this->userId . '.shareKey'));
	}

	/**
	 * handle webdav request
	 *
	 * @param bool $body
	 * @note this init procedure is copied from /apps/files/appinfo/remote.php
	 */
	function handleWebdavRequest($body = false) {
		// Backends
		$authBackend = $this->getMockBuilder('OC\Connector\Sabre\Auth')
			->setMethods(['validateUserPass'])
			->getMock();
		$authBackend->expects($this->any())
			->method('validateUserPass')
			->will($this->returnValue(true));

		$lockBackend = new \OC\Connector\Sabre\Locks();

		// Create ownCloud Dir
		$root = '/' . $this->userId . '/files';
		$view = new \OC\Files\View($root);
		$publicDir = new \OC\Connector\Sabre\Directory($view, $view->getFileInfo(''));
		$objectTree = new \OC\Connector\Sabre\ObjectTree();
		$mountManager = \OC\Files\Filesystem::getMountManager();
		$objectTree->init($publicDir, $view, $mountManager);

		// Fire up server
		$server = new \Sabre\DAV\Server($publicDir);
		$server->setBaseUri('/remote.php/webdav/');

		// Load plugins
		$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'ownCloud'));
		$server->addPlugin(new \Sabre\DAV\Locks\Plugin($lockBackend));
		$server->addPlugin(new \Sabre\DAV\Browser\Plugin(false)); // Show something in the Browser, but no upload
		$server->addPlugin(new \OC\Connector\Sabre\QuotaPlugin($view));
		$server->addPlugin(new \OC\Connector\Sabre\MaintenancePlugin());
		$server->debugExceptions = true;

		// Totally ugly hack to setup the FS
		\OC::$server->getUserSession()->login($this->userId, $this->userId);
		\OC_Util::setupFS($this->userId);

		// And off we go!
		if ($body) {
			$server->httpRequest->setBody($body);
		}

		// turn on output buffering
		ob_start();

		// handle request
		$server->exec();

		// file content is written in the output buffer
		$content = ob_get_contents();

		// flush the output buffer and turn off output buffering
		ob_end_clean();

		// return captured content
		return $content;
	}
}
