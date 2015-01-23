<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_trashbin\Tests\Storage;

use OC\Files\Storage\Home;
use OC\Files\Storage\Temporary;
use OC\Files\Mount\MountPoint;
use OC\Files\Filesystem;

class Storage extends \Test\TestCase {
	/**
	 * @var \OCA\Files_trashbin\Storage
	 */
	private $wrapper;

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var \OC\Files\Storage\Storage
	 **/
	private $originalStorage;

	/**
	 * @var \OC\Files\View
	 */
	private $rootView;

	/**
	 * @var \OC\Files\View
	 */
	private $userView;

	protected function setUp() {
		parent::setUp();

		$this->user = $this->getUniqueId('user');
		\OC_User::createUser($this->user, $this->user);

		// this will setup the FS
		$this->loginAsUser($this->user);

		$this->originalStorage = \OC\Files\Filesystem::getStorage('/');

		\OCA\Files_Trashbin\Storage::setupStorage();

		$this->rootView = new \OC\Files\View('/');
		$this->userView = new \OC\Files\View('/' . $this->user . '/files/');
		$this->userView->file_put_contents('test.txt', 'foo');

	}

	protected function tearDown() {
		\OC\Files\Filesystem::getLoader()->removeStorageWrapper('oc_trashbin');
		\OC\Files\Filesystem::mount($this->originalStorage, array(), '/');
		$this->logout();
		\OC_User::deleteUser($this->user);
		parent::tearDown();
	}

	public function testSingleStorageDelete() {
		$this->assertTrue($this->userView->file_exists('test.txt'));
		$this->userView->unlink('test.txt');
		list($storage, ) = $this->userView->resolvePath('test.txt');
		$storage->getScanner()->scan(''); // make sure we check the storage
		$this->assertFalse($this->userView->getFileInfo('test.txt'));

		// check if file is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertEquals(1, count($results));
		$name = $results[0]->getName();
		$this->assertEquals('test.txt', substr($name, 0, strrpos($name, '.')));
	}

	public function testCrossStorageDelete() {
		$storage2 = new Temporary(array());
		\OC\Files\Filesystem::mount($storage2, array(), $this->user . '/files/substorage');

		$this->userView->file_put_contents('substorage/subfile.txt', 'foo');
		$storage2->getScanner()->scan('');
		$this->assertTrue($storage2->file_exists('subfile.txt'));
		$this->userView->unlink('substorage/subfile.txt');

		$storage2->getScanner()->scan('');
		$this->assertFalse($this->userView->getFileInfo('substorage/subfile.txt'));
		$this->assertFalse($storage2->file_exists('subfile.txt'));

		// check if file is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertEquals(1, count($results));
		$name = $results[0]->getName();
		$this->assertEquals('subfile.txt', substr($name, 0, strrpos($name, '.')));
	}
}
