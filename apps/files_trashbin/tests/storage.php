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

		\OC_Hook::clear();
		\OCA\Files_Trashbin\Trashbin::registerHooks();

		$this->user = $this->getUniqueId('user');
		\OC::$server->getUserManager()->createUser($this->user, $this->user);

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
		\OC_Hook::clear();
		parent::tearDown();
	}

	/**
	 * Test that deleting a file puts it into the trashbin.
	 */
	public function testSingleStorageDelete() {
		$this->assertTrue($this->userView->file_exists('test.txt'));
		$this->userView->unlink('test.txt');
		list($storage,) = $this->userView->resolvePath('test.txt');
		$storage->getScanner()->scan(''); // make sure we check the storage
		$this->assertFalse($this->userView->getFileInfo('test.txt'));

		// check if file is in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertEquals(1, count($results));
		$name = $results[0]->getName();
		$this->assertEquals('test.txt', substr($name, 0, strrpos($name, '.')));
	}

	/**
	 * Test that deleting a file from another mounted storage properly
	 * lands in the trashbin. This is a cross-storage situation because
	 * the trashbin folder is in the root storage while the mounted one
	 * isn't.
	 */
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

	/**
	 * Test that deleted versions properly land in the trashbin.
	 */
	public function testDeleteVersions() {
		\OCA\Files_Versions\Hooks::connectHooks();

		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('test.txt', 'v1');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/');
		$this->assertEquals(1, count($results));

		$this->userView->unlink('test.txt');

		// rescan trash storage
		list($rootStorage,) = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// check if versions are in trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions');
		$this->assertEquals(1, count($results));
		$name = $results[0]->getName();
		$this->assertEquals('test.txt', substr($name, 0, strlen('test.txt')));
	}

	/**
	 * Test that versions are not auto-trashed when moving a file between
	 * storages. This is because rename() between storages would call
	 * unlink() which should NOT trigger the version deletion logic.
	 */
	public function testKeepFileAndVersionsWhenMovingBetweenStorages() {
		\OCA\Files_Versions\Hooks::connectHooks();

		$storage2 = new Temporary(array());
		\OC\Files\Filesystem::mount($storage2, array(), $this->user . '/files/substorage');

		// trigger a version (multiple would not work because of the expire logic)
		$this->userView->file_put_contents('test.txt', 'v1');

		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertEquals(0, count($results));

		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/');
		$this->assertEquals(1, count($results));

		// move to another storage
		$this->userView->rename('test.txt', 'substorage/test.txt');
		$this->userView->file_exists('substorage/test.txt');

		// rescan trash storage
		list($rootStorage,) = $this->rootView->resolvePath($this->user . '/files_trashbin');
		$rootStorage->getScanner()->scan('');

		// versions were moved too
		$results = $this->rootView->getDirectoryContent($this->user . '/files_versions/substorage');
		$this->assertEquals(1, count($results));

		// check that nothing got trashed by the rename's unlink() call
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertEquals(0, count($results));

		// check that versions were moved and not trashed
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/versions/');
		$this->assertEquals(0, count($results));
	}

	/**
	 * Delete should fail is the source file cant be deleted
	 */
	public function testSingleStorageDeleteFail() {
		/**
		 * @var \OC\Files\Storage\Temporary | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setConstructorArgs([[]])
			->setMethods(['rename', 'unlink'])
			->getMock();

		$storage->expects($this->any())
			->method('rename')
			->will($this->returnValue(false));
		$storage->expects($this->any())
			->method('unlink')
			->will($this->returnValue(false));

		$cache = $storage->getCache();

		Filesystem::mount($storage, [], '/' . $this->user . '/files');
		$this->userView->file_put_contents('test.txt', 'foo');
		$this->assertTrue($storage->file_exists('test.txt'));
		$this->assertFalse($this->userView->unlink('test.txt'));
		$this->assertTrue($storage->file_exists('test.txt'));
		$this->assertTrue($cache->inCache('test.txt'));

		// file should not be in the trashbin
		$results = $this->rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertEquals(0, count($results));
	}
}
