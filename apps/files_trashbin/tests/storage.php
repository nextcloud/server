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
	 * @var \OCP\Files\Storage
	 */
	private $storage;

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
	private $userView;

	protected function setUp() {
		parent::setUp();

		$this->user = $this->getUniqueId('user');
		\OC_User::createUser($this->user, $this->user);

		// this will setup the FS
		$this->loginAsUser($this->user);

		$this->originalStorage = \OC\Files\Filesystem::getStorage('/');

		$mockUser = $this->getMock('\OCP\IUser');
		$mockUser->expects($this->any())
			->method('getHome')
			->will($this->returnValue($this->originalStorage->getLocalFolder($this->user)));
		$mockUser->expects($this->any())
			->method('getUID')
			->will($this->returnValue($this->user));

		// use temp as root storage so we can wrap it for testing
		$this->storage = new Home(
			array('user' => $mockUser)
		);
		$this->wrapper = new \OCA\Files_Trashbin\Storage(
			array(
				'storage' => $this->storage,
				'mountPoint' => $this->user,
			)
		);

		// make room for a new root
		Filesystem::clearMounts();
		$rootMount = new MountPoint($this->originalStorage, '');
		Filesystem::getMountManager()->addMount($rootMount);
		$homeMount = new MountPoint($this->wrapper, $this->user);
		Filesystem::getMountManager()->addMount($homeMount);

		$this->userView = new \OC\Files\View('/' . $this->user . '/files/');
		$this->userView->file_put_contents('test.txt', 'foo');
	}

	protected function tearDown() {
		\OC\Files\Filesystem::mount($this->originalStorage, array(), '/');
		$this->logout();
		parent::tearDown();
	}

	public function testSingleStorageDelete() {
		$this->assertTrue($this->storage->file_exists('files/test.txt'));
		$this->userView->unlink('test.txt');
		$this->storage->getScanner()->scan('');
		$this->assertFalse($this->userView->getFileInfo('test.txt'));
		$this->assertFalse($this->storage->file_exists('files/test.txt'));

		// check if file is in trashbin
		$rootView = new \OC\Files\View('/');
		$results = $rootView->getDirectoryContent($this->user . '/files_trashbin/files/');
		$this->assertEquals(1, count($results));
		$name = $results[0]->getName();
		$this->assertEquals('test.txt', substr($name, 0, strrpos($name, '.')));
	}

	public function testCrossStorageDelete() {
		$storage2 = new Temporary(array());
		$wrapper2 = new \OCA\Files_Trashbin\Storage(
			array(
				'storage' => $storage2,
				'mountPoint' => $this->user . '/files/substorage',
			)
		);

		$mount = new MountPoint($wrapper2, $this->user . '/files/substorage');
		Filesystem::getMountManager()->addMount($mount);

		$this->userView->file_put_contents('substorage/subfile.txt', 'foo');
		$storage2->getScanner()->scan('');
		$this->assertTrue($storage2->file_exists('subfile.txt'));
		$this->userView->unlink('substorage/subfile.txt');

		$storage2->getScanner()->scan('');
		$this->assertFalse($this->userView->getFileInfo('substorage/subfile.txt'));
		$this->assertFalse($storage2->file_exists('subfile.txt'));

		// check if file is in trashbin
		$rootView = new \OC\Files\View('/');
		$results = $rootView->getDirectoryContent($this->user . '/files_trashbin/files');
		$this->assertEquals(1, count($results));
		$name = $results[0]->getName();
		$this->assertEquals('subfile.txt', substr($name, 0, strrpos($name, '.')));
	}
}
