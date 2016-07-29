<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file. */

namespace Test\Files;

use OC\Cache\CappedMemoryCache;
use OC\Files\Cache\Watcher;
use OC\Files\Storage\Common;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Temporary;
use OCP\Files\FileInfo;
use OCP\Lock\ILockingProvider;

class TemporaryNoTouch extends \OC\Files\Storage\Temporary {
	public function touch($path, $mtime = null) {
		return false;
	}
}

class TemporaryNoCross extends \OC\Files\Storage\Temporary {
	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		return Common::copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		return Common::moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}
}

class TemporaryNoLocal extends \OC\Files\Storage\Temporary {
	public function instanceOfStorage($className) {
		if ($className === '\OC\Files\Storage\Local') {
			return false;
		} else {
			return parent::instanceOfStorage($className);
		}
	}
}

/**
 * Class ViewTest
 *
 * @group DB
 *
 * @package Test\Files
 */
class ViewTest extends \Test\TestCase {
	/**
	 * @var \OC\Files\Storage\Storage[] $storages
	 */
	private $storages = array();

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var \OCP\IUser
	 */
	private $userObject;

	/**
	 * @var \OCP\IGroup
	 */
	private $groupObject;

	/** @var \OC\Files\Storage\Storage */
	private $tempStorage;

	protected function setUp() {
		parent::setUp();
		\OC_Hook::clear();

		\OC_User::clearBackends();
		\OC_User::useBackend(new \Test\Util\User\Dummy());

		//login
		$userManager = \OC::$server->getUserManager();
		$groupManager = \OC::$server->getGroupManager();
		$this->user = 'test';
		$this->userObject = $userManager->createUser('test', 'test');

		$this->groupObject = $groupManager->createGroup('group1');
		$this->groupObject->addUser($this->userObject);

		$this->loginAsUser($this->user);
		// clear mounts but somehow keep the root storage
		// that was initialized above...
		\OC\Files\Filesystem::clearMounts();

		$this->tempStorage = null;
	}

	protected function tearDown() {
		\OC_User::setUserId($this->user);
		foreach ($this->storages as $storage) {
			$cache = $storage->getCache();
			$ids = $cache->getAll();
			$cache->clear();
		}

		if ($this->tempStorage) {
			system('rm -rf ' . escapeshellarg($this->tempStorage->getDataDir()));
		}

		$this->logout();

		$this->userObject->delete();
		$this->groupObject->delete();

		$mountProviderCollection = \OC::$server->getMountProviderCollection();
		\Test\TestCase::invokePrivate($mountProviderCollection, 'providers', [[]]);

		parent::tearDown();
	}

	/**
	 * @medium
	 */
	public function testCacheAPI() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$storage3 = $this->getTestStorage();
		$root = $this->getUniqueID('/');
		\OC\Files\Filesystem::mount($storage1, array(), $root . '/');
		\OC\Files\Filesystem::mount($storage2, array(), $root . '/substorage');
		\OC\Files\Filesystem::mount($storage3, array(), $root . '/folder/anotherstorage');
		$textSize = strlen("dummy file data\n");
		$imageSize = filesize(\OC::$SERVERROOT . '/core/img/logo.png');
		$storageSize = $textSize * 2 + $imageSize;

		$storageInfo = $storage3->getCache()->get('');
		$this->assertEquals($storageSize, $storageInfo['size']);

		$rootView = new \OC\Files\View($root);

		$cachedData = $rootView->getFileInfo('/foo.txt');
		$this->assertEquals($textSize, $cachedData['size']);
		$this->assertEquals('text/plain', $cachedData['mimetype']);
		$this->assertNotEquals(-1, $cachedData['permissions']);

		$cachedData = $rootView->getFileInfo('/');
		$this->assertEquals($storageSize * 3, $cachedData['size']);
		$this->assertEquals('httpd/unix-directory', $cachedData['mimetype']);

		// get cached data excluding mount points
		$cachedData = $rootView->getFileInfo('/', false);
		$this->assertEquals($storageSize, $cachedData['size']);
		$this->assertEquals('httpd/unix-directory', $cachedData['mimetype']);

		$cachedData = $rootView->getFileInfo('/folder');
		$this->assertEquals($storageSize + $textSize, $cachedData['size']);
		$this->assertEquals('httpd/unix-directory', $cachedData['mimetype']);

		$folderData = $rootView->getDirectoryContent('/');
		/**
		 * expected entries:
		 * folder
		 * foo.png
		 * foo.txt
		 * substorage
		 */
		$this->assertEquals(4, count($folderData));
		$this->assertEquals('folder', $folderData[0]['name']);
		$this->assertEquals('foo.png', $folderData[1]['name']);
		$this->assertEquals('foo.txt', $folderData[2]['name']);
		$this->assertEquals('substorage', $folderData[3]['name']);

		$this->assertEquals($storageSize + $textSize, $folderData[0]['size']);
		$this->assertEquals($imageSize, $folderData[1]['size']);
		$this->assertEquals($textSize, $folderData[2]['size']);
		$this->assertEquals($storageSize, $folderData[3]['size']);

		$folderData = $rootView->getDirectoryContent('/substorage');
		/**
		 * expected entries:
		 * folder
		 * foo.png
		 * foo.txt
		 */
		$this->assertEquals(3, count($folderData));
		$this->assertEquals('folder', $folderData[0]['name']);
		$this->assertEquals('foo.png', $folderData[1]['name']);
		$this->assertEquals('foo.txt', $folderData[2]['name']);

		$folderView = new \OC\Files\View($root . '/folder');
		$this->assertEquals($rootView->getFileInfo('/folder'), $folderView->getFileInfo('/'));

		$cachedData = $rootView->getFileInfo('/foo.txt');
		$this->assertFalse($cachedData['encrypted']);
		$id = $rootView->putFileInfo('/foo.txt', array('encrypted' => true));
		$cachedData = $rootView->getFileInfo('/foo.txt');
		$this->assertTrue($cachedData['encrypted']);
		$this->assertEquals($cachedData['fileid'], $id);

		$this->assertFalse($rootView->getFileInfo('/non/existing'));
		$this->assertEquals(array(), $rootView->getDirectoryContent('/non/existing'));
	}

	/**
	 * @medium
	 */
	public function testGetPath() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$storage3 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');
		\OC\Files\Filesystem::mount($storage3, array(), '/folder/anotherstorage');

		$rootView = new \OC\Files\View('');

		$cachedData = $rootView->getFileInfo('/foo.txt');
		/** @var int $id1 */
		$id1 = $cachedData['fileid'];
		$this->assertEquals('/foo.txt', $rootView->getPath($id1));

		$cachedData = $rootView->getFileInfo('/substorage/foo.txt');
		/** @var int $id2 */
		$id2 = $cachedData['fileid'];
		$this->assertEquals('/substorage/foo.txt', $rootView->getPath($id2));

		$folderView = new \OC\Files\View('/substorage');
		$this->assertEquals('/foo.txt', $folderView->getPath($id2));
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	function testGetPathNotExisting() {
		$storage1 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, [], '/');

		$rootView = new \OC\Files\View('');
		$cachedData = $rootView->getFileInfo('/foo.txt');
		/** @var int $id1 */
		$id1 = $cachedData['fileid'];
		$folderView = new \OC\Files\View('/substorage');
		$this->assertNull($folderView->getPath($id1));
	}

	/**
	 * @medium
	 */
	public function testMountPointOverwrite() {
		$storage1 = $this->getTestStorage(false);
		$storage2 = $this->getTestStorage();
		$storage1->mkdir('substorage');
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');

		$rootView = new \OC\Files\View('');
		$folderContent = $rootView->getDirectoryContent('/');
		$this->assertEquals(4, count($folderContent));
	}

	public function sharingDisabledPermissionProvider() {
		return [
			['no', '', true],
			['yes', 'group1', false],
		];
	}

	/**
	 * @dataProvider sharingDisabledPermissionProvider
	 */
	public function testRemoveSharePermissionWhenSharingDisabledForUser($excludeGroups, $excludeGroupsList, $expectedShareable) {
		// Reset sharing disabled for users cache
		$this->invokePrivate(\OC::$server->getShareManager(), 'sharingDisabledForUsersCache', [new CappedMemoryCache()]);

		$appConfig = \OC::$server->getAppConfig();
		$oldExcludeGroupsFlag = $appConfig->getValue('core', 'shareapi_exclude_groups', 'no');
		$oldExcludeGroupsList = $appConfig->getValue('core', 'shareapi_exclude_groups_list', '');
		$appConfig->setValue('core', 'shareapi_exclude_groups', $excludeGroups);
		$appConfig->setValue('core', 'shareapi_exclude_groups_list', $excludeGroupsList);

		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/mount');

		$view = new \OC\Files\View('/');

		$folderContent = $view->getDirectoryContent('');
		$this->assertEquals($expectedShareable, $folderContent[0]->isShareable());

		$folderContent = $view->getDirectoryContent('mount');
		$this->assertEquals($expectedShareable, $folderContent[0]->isShareable());

		$appConfig->setValue('core', 'shareapi_exclude_groups', $oldExcludeGroupsFlag);
		$appConfig->setValue('core', 'shareapi_exclude_groups_list', $oldExcludeGroupsList);

		// Reset sharing disabled for users cache
		$this->invokePrivate(\OC::$server->getShareManager(), 'sharingDisabledForUsersCache', [new CappedMemoryCache()]);
	}

	public function testCacheIncompleteFolder() {
		$storage1 = $this->getTestStorage(false);
		\OC\Files\Filesystem::clearMounts();
		\OC\Files\Filesystem::mount($storage1, array(), '/incomplete');
		$rootView = new \OC\Files\View('/incomplete');

		$entries = $rootView->getDirectoryContent('/');
		$this->assertEquals(3, count($entries));

		// /folder will already be in the cache but not scanned
		$entries = $rootView->getDirectoryContent('/folder');
		$this->assertEquals(1, count($entries));
	}

	public function testAutoScan() {
		$storage1 = $this->getTestStorage(false);
		$storage2 = $this->getTestStorage(false);
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');
		$textSize = strlen("dummy file data\n");

		$rootView = new \OC\Files\View('');

		$cachedData = $rootView->getFileInfo('/');
		$this->assertEquals('httpd/unix-directory', $cachedData['mimetype']);
		$this->assertEquals(-1, $cachedData['size']);

		$folderData = $rootView->getDirectoryContent('/substorage/folder');
		$this->assertEquals('text/plain', $folderData[0]['mimetype']);
		$this->assertEquals($textSize, $folderData[0]['size']);
	}

	/**
	 * @medium
	 */
	public function testSearch() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$storage3 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');
		\OC\Files\Filesystem::mount($storage3, array(), '/folder/anotherstorage');

		$rootView = new \OC\Files\View('');

		$results = $rootView->search('foo');
		$this->assertEquals(6, count($results));
		$paths = array();
		foreach ($results as $result) {
			$this->assertEquals($result['path'], \OC\Files\Filesystem::normalizePath($result['path']));
			$paths[] = $result['path'];
		}
		$this->assertContains('/foo.txt', $paths);
		$this->assertContains('/foo.png', $paths);
		$this->assertContains('/substorage/foo.txt', $paths);
		$this->assertContains('/substorage/foo.png', $paths);
		$this->assertContains('/folder/anotherstorage/foo.txt', $paths);
		$this->assertContains('/folder/anotherstorage/foo.png', $paths);

		$folderView = new \OC\Files\View('/folder');
		$results = $folderView->search('bar');
		$this->assertEquals(2, count($results));
		$paths = array();
		foreach ($results as $result) {
			$paths[] = $result['path'];
		}
		$this->assertContains('/anotherstorage/folder/bar.txt', $paths);
		$this->assertContains('/bar.txt', $paths);

		$results = $folderView->search('foo');
		$this->assertEquals(2, count($results));
		$paths = array();
		foreach ($results as $result) {
			$paths[] = $result['path'];
		}
		$this->assertContains('/anotherstorage/foo.txt', $paths);
		$this->assertContains('/anotherstorage/foo.png', $paths);

		$this->assertEquals(6, count($rootView->searchByMime('text')));
		$this->assertEquals(3, count($folderView->searchByMime('text')));
	}

	/**
	 * @medium
	 */
	public function testWatcher() {
		$storage1 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		$storage1->getWatcher()->setPolicy(Watcher::CHECK_ALWAYS);

		$rootView = new \OC\Files\View('');

		$cachedData = $rootView->getFileInfo('foo.txt');
		$this->assertEquals(16, $cachedData['size']);

		$rootView->putFileInfo('foo.txt', array('storage_mtime' => 10));
		$storage1->file_put_contents('foo.txt', 'foo');
		clearstatcache();

		$cachedData = $rootView->getFileInfo('foo.txt');
		$this->assertEquals(3, $cachedData['size']);
	}

	/**
	 * @medium
	 */
	public function testCopyBetweenStorageNoCross() {
		$storage1 = $this->getTestStorage(true, '\Test\Files\TemporaryNoCross');
		$storage2 = $this->getTestStorage(true, '\Test\Files\TemporaryNoCross');
		$this->copyBetweenStorages($storage1, $storage2);
	}

	/**
	 * @medium
	 */
	public function testCopyBetweenStorageCross() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$this->copyBetweenStorages($storage1, $storage2);
	}

	/**
	 * @medium
	 */
	public function testCopyBetweenStorageCrossNonLocal() {
		$storage1 = $this->getTestStorage(true, '\Test\Files\TemporaryNoLocal');
		$storage2 = $this->getTestStorage(true, '\Test\Files\TemporaryNoLocal');
		$this->copyBetweenStorages($storage1, $storage2);
	}

	function copyBetweenStorages($storage1, $storage2) {
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');

		$rootView = new \OC\Files\View('');
		$rootView->mkdir('substorage/emptyfolder');
		$rootView->copy('substorage', 'anotherfolder');
		$this->assertTrue($rootView->is_dir('/anotherfolder'));
		$this->assertTrue($rootView->is_dir('/substorage'));
		$this->assertTrue($rootView->is_dir('/anotherfolder/emptyfolder'));
		$this->assertTrue($rootView->is_dir('/substorage/emptyfolder'));
		$this->assertTrue($rootView->file_exists('/anotherfolder/foo.txt'));
		$this->assertTrue($rootView->file_exists('/anotherfolder/foo.png'));
		$this->assertTrue($rootView->file_exists('/anotherfolder/folder/bar.txt'));
		$this->assertTrue($rootView->file_exists('/substorage/foo.txt'));
		$this->assertTrue($rootView->file_exists('/substorage/foo.png'));
		$this->assertTrue($rootView->file_exists('/substorage/folder/bar.txt'));
	}

	/**
	 * @medium
	 */
	public function testMoveBetweenStorageNoCross() {
		$storage1 = $this->getTestStorage(true, '\Test\Files\TemporaryNoCross');
		$storage2 = $this->getTestStorage(true, '\Test\Files\TemporaryNoCross');
		$this->moveBetweenStorages($storage1, $storage2);
	}

	/**
	 * @medium
	 */
	public function testMoveBetweenStorageCross() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$this->moveBetweenStorages($storage1, $storage2);
	}

	/**
	 * @medium
	 */
	public function testMoveBetweenStorageCrossNonLocal() {
		$storage1 = $this->getTestStorage(true, '\Test\Files\TemporaryNoLocal');
		$storage2 = $this->getTestStorage(true, '\Test\Files\TemporaryNoLocal');
		$this->moveBetweenStorages($storage1, $storage2);
	}

	function moveBetweenStorages($storage1, $storage2) {
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');

		$rootView = new \OC\Files\View('');
		$rootView->rename('foo.txt', 'substorage/folder/foo.txt');
		$this->assertFalse($rootView->file_exists('foo.txt'));
		$this->assertTrue($rootView->file_exists('substorage/folder/foo.txt'));
		$rootView->rename('substorage/folder', 'anotherfolder');
		$this->assertFalse($rootView->is_dir('substorage/folder'));
		$this->assertTrue($rootView->file_exists('anotherfolder/foo.txt'));
		$this->assertTrue($rootView->file_exists('anotherfolder/bar.txt'));
	}

	/**
	 * @medium
	 */
	public function testUnlink() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');

		$rootView = new \OC\Files\View('');
		$rootView->file_put_contents('/foo.txt', 'asd');
		$rootView->file_put_contents('/substorage/bar.txt', 'asd');

		$this->assertTrue($rootView->file_exists('foo.txt'));
		$this->assertTrue($rootView->file_exists('substorage/bar.txt'));

		$this->assertTrue($rootView->unlink('foo.txt'));
		$this->assertTrue($rootView->unlink('substorage/bar.txt'));

		$this->assertFalse($rootView->file_exists('foo.txt'));
		$this->assertFalse($rootView->file_exists('substorage/bar.txt'));
	}

	/**
	 * @medium
	 */
	public function testUnlinkRootMustFail() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');

		$rootView = new \OC\Files\View('');
		$rootView->file_put_contents('/foo.txt', 'asd');
		$rootView->file_put_contents('/substorage/bar.txt', 'asd');

		$this->assertFalse($rootView->unlink(''));
		$this->assertFalse($rootView->unlink('/'));
		$this->assertFalse($rootView->unlink('substorage'));
		$this->assertFalse($rootView->unlink('/substorage'));
	}

	/**
	 * @medium
	 */
	public function testTouch() {
		$storage = $this->getTestStorage(true, '\Test\Files\TemporaryNoTouch');

		\OC\Files\Filesystem::mount($storage, array(), '/');

		$rootView = new \OC\Files\View('');
		$oldCachedData = $rootView->getFileInfo('foo.txt');

		$rootView->touch('foo.txt', 500);

		$cachedData = $rootView->getFileInfo('foo.txt');
		$this->assertEquals(500, $cachedData['mtime']);
		$this->assertEquals($oldCachedData['storage_mtime'], $cachedData['storage_mtime']);

		$rootView->putFileInfo('foo.txt', array('storage_mtime' => 1000)); //make sure the watcher detects the change
		$rootView->file_put_contents('foo.txt', 'asd');
		$cachedData = $rootView->getFileInfo('foo.txt');
		$this->assertGreaterThanOrEqual($oldCachedData['mtime'], $cachedData['mtime']);
		$this->assertEquals($cachedData['storage_mtime'], $cachedData['mtime']);
	}

	/**
	 * @medium
	 */
	public function testViewHooks() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$defaultRoot = \OC\Files\Filesystem::getRoot();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), $defaultRoot . '/substorage');
		\OC_Hook::connect('OC_Filesystem', 'post_write', $this, 'dummyHook');

		$rootView = new \OC\Files\View('');
		$subView = new \OC\Files\View($defaultRoot . '/substorage');
		$this->hookPath = null;

		$rootView->file_put_contents('/foo.txt', 'asd');
		$this->assertNull($this->hookPath);

		$subView->file_put_contents('/foo.txt', 'asd');
		$this->assertEquals('/substorage/foo.txt', $this->hookPath);
	}

	private $hookPath;

	public function dummyHook($params) {
		$this->hookPath = $params['path'];
	}

	public function testSearchNotOutsideView() {
		$storage1 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		$storage1->rename('folder', 'foo');
		$scanner = $storage1->getScanner();
		$scanner->scan('');

		$view = new \OC\Files\View('/foo');

		$result = $view->search('.txt');
		$this->assertCount(1, $result);
	}

	/**
	 * @param bool $scan
	 * @param string $class
	 * @return \OC\Files\Storage\Storage
	 */
	private function getTestStorage($scan = true, $class = '\OC\Files\Storage\Temporary') {
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$storage = new $class(array());
		$textData = "dummy file data\n";
		$imgData = file_get_contents(\OC::$SERVERROOT . '/core/img/logo.png');
		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', $textData);
		$storage->file_put_contents('foo.png', $imgData);
		$storage->file_put_contents('folder/bar.txt', $textData);

		if ($scan) {
			$scanner = $storage->getScanner();
			$scanner->scan('');
		}
		$this->storages[] = $storage;
		return $storage;
	}

	/**
	 * @medium
	 */
	public function testViewHooksIfRootStartsTheSame() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$defaultRoot = \OC\Files\Filesystem::getRoot();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), $defaultRoot . '_substorage');
		\OC_Hook::connect('OC_Filesystem', 'post_write', $this, 'dummyHook');

		$subView = new \OC\Files\View($defaultRoot . '_substorage');
		$this->hookPath = null;

		$subView->file_put_contents('/foo.txt', 'asd');
		$this->assertNull($this->hookPath);
	}

	private $hookWritePath;
	private $hookCreatePath;
	private $hookUpdatePath;

	public function dummyHookWrite($params) {
		$this->hookWritePath = $params['path'];
	}

	public function dummyHookUpdate($params) {
		$this->hookUpdatePath = $params['path'];
	}

	public function dummyHookCreate($params) {
		$this->hookCreatePath = $params['path'];
	}

	public function testEditNoCreateHook() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$defaultRoot = \OC\Files\Filesystem::getRoot();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), $defaultRoot);
		\OC_Hook::connect('OC_Filesystem', 'post_create', $this, 'dummyHookCreate');
		\OC_Hook::connect('OC_Filesystem', 'post_update', $this, 'dummyHookUpdate');
		\OC_Hook::connect('OC_Filesystem', 'post_write', $this, 'dummyHookWrite');

		$view = new \OC\Files\View($defaultRoot);
		$this->hookWritePath = $this->hookUpdatePath = $this->hookCreatePath = null;

		$view->file_put_contents('/asd.txt', 'foo');
		$this->assertEquals('/asd.txt', $this->hookCreatePath);
		$this->assertNull($this->hookUpdatePath);
		$this->assertEquals('/asd.txt', $this->hookWritePath);

		$this->hookWritePath = $this->hookUpdatePath = $this->hookCreatePath = null;

		$view->file_put_contents('/asd.txt', 'foo');
		$this->assertNull($this->hookCreatePath);
		$this->assertEquals('/asd.txt', $this->hookUpdatePath);
		$this->assertEquals('/asd.txt', $this->hookWritePath);

		\OC_Hook::clear('OC_Filesystem', 'post_create');
		\OC_Hook::clear('OC_Filesystem', 'post_update');
		\OC_Hook::clear('OC_Filesystem', 'post_write');
	}

	/**
	 * @dataProvider resolvePathTestProvider
	 */
	public function testResolvePath($expected, $pathToTest) {
		$storage1 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');

		$view = new \OC\Files\View('');

		$result = $view->resolvePath($pathToTest);
		$this->assertEquals($expected, $result[1]);

		$exists = $view->file_exists($pathToTest);
		$this->assertTrue($exists);

		$exists = $view->file_exists($result[1]);
		$this->assertTrue($exists);
	}

	function resolvePathTestProvider() {
		return array(
			array('foo.txt', 'foo.txt'),
			array('foo.txt', '/foo.txt'),
			array('folder', 'folder'),
			array('folder', '/folder'),
			array('folder', 'folder/'),
			array('folder', '/folder/'),
			array('folder/bar.txt', 'folder/bar.txt'),
			array('folder/bar.txt', '/folder/bar.txt'),
			array('', ''),
			array('', '/'),
		);
	}

	public function testUTF8Names() {
		$names = array('虚', '和知しゃ和で', 'regular ascii', 'sɨˈrɪlɪk', 'ѨѬ', 'أنا أحب القراءة كثيرا');

		$storage = new \OC\Files\Storage\Temporary(array());
		\OC\Files\Filesystem::mount($storage, array(), '/');

		$rootView = new \OC\Files\View('');
		foreach ($names as $name) {
			$rootView->file_put_contents('/' . $name, 'dummy content');
		}

		$list = $rootView->getDirectoryContent('/');

		$this->assertCount(count($names), $list);
		foreach ($list as $item) {
			$this->assertContains($item['name'], $names);
		}

		$cache = $storage->getCache();
		$scanner = $storage->getScanner();
		$scanner->scan('');

		$list = $cache->getFolderContents('');

		$this->assertCount(count($names), $list);
		foreach ($list as $item) {
			$this->assertContains($item['name'], $names);
		}
	}

	public function xtestLongPath() {

		$storage = new \OC\Files\Storage\Temporary(array());
		\OC\Files\Filesystem::mount($storage, array(), '/');

		$rootView = new \OC\Files\View('');

		$longPath = '';
		$ds = DIRECTORY_SEPARATOR;
		/*
		 * 4096 is the maximum path length in file_cache.path in *nix
		 * 1024 is the max path length in mac
		 */
		$folderName = 'abcdefghijklmnopqrstuvwxyz012345678901234567890123456789';
		$tmpdirLength = strlen(\OC::$server->getTempManager()->getTemporaryFolder());
		if (\OC_Util::runningOnMac()) {
			$depth = ((1024 - $tmpdirLength) / 57);
		} else {
			$depth = ((4000 - $tmpdirLength) / 57);
		}
		foreach (range(0, $depth - 1) as $i) {
			$longPath .= $ds . $folderName;
			$result = $rootView->mkdir($longPath);
			$this->assertTrue($result, "mkdir failed on $i - path length: " . strlen($longPath));

			$result = $rootView->file_put_contents($longPath . "{$ds}test.txt", 'lorem');
			$this->assertEquals(5, $result, "file_put_contents failed on $i");

			$this->assertTrue($rootView->file_exists($longPath));
			$this->assertTrue($rootView->file_exists($longPath . "{$ds}test.txt"));
		}

		$cache = $storage->getCache();
		$scanner = $storage->getScanner();
		$scanner->scan('');

		$longPath = $folderName;
		foreach (range(0, $depth - 1) as $i) {
			$cachedFolder = $cache->get($longPath);
			$this->assertTrue(is_array($cachedFolder), "No cache entry for folder at $i");
			$this->assertEquals($folderName, $cachedFolder['name'], "Wrong cache entry for folder at $i");

			$cachedFile = $cache->get($longPath . '/test.txt');
			$this->assertTrue(is_array($cachedFile), "No cache entry for file at $i");
			$this->assertEquals('test.txt', $cachedFile['name'], "Wrong cache entry for file at $i");

			$longPath .= $ds . $folderName;
		}
	}

	public function testTouchNotSupported() {
		$storage = new TemporaryNoTouch(array());
		$scanner = $storage->getScanner();
		\OC\Files\Filesystem::mount($storage, array(), '/test/');
		$past = time() - 100;
		$storage->file_put_contents('test', 'foobar');
		$scanner->scan('');
		$view = new \OC\Files\View('');
		$info = $view->getFileInfo('/test/test');

		$view->touch('/test/test', $past);
		$scanner->scanFile('test', \OC\Files\Cache\Scanner::REUSE_ETAG);

		$info2 = $view->getFileInfo('/test/test');
		$this->assertSame($info['etag'], $info2['etag']);
	}

	public function testWatcherEtagCrossStorage() {
		$storage1 = new Temporary(array());
		$storage2 = new Temporary(array());
		$scanner1 = $storage1->getScanner();
		$scanner2 = $storage2->getScanner();
		$storage1->mkdir('sub');
		\OC\Files\Filesystem::mount($storage1, array(), '/test/');
		\OC\Files\Filesystem::mount($storage2, array(), '/test/sub/storage');

		$past = time() - 100;
		$storage2->file_put_contents('test.txt', 'foobar');
		$scanner1->scan('');
		$scanner2->scan('');
		$view = new \OC\Files\View('');

		$storage2->getWatcher('')->setPolicy(Watcher::CHECK_ALWAYS);

		$oldFileInfo = $view->getFileInfo('/test/sub/storage/test.txt');
		$oldFolderInfo = $view->getFileInfo('/test');

		$storage2->getCache()->update($oldFileInfo->getId(), array(
			'storage_mtime' => $past
		));

		$view->getFileInfo('/test/sub/storage/test.txt');
		$newFolderInfo = $view->getFileInfo('/test');

		$this->assertNotEquals($newFolderInfo->getEtag(), $oldFolderInfo->getEtag());
	}

	/**
	 * @dataProvider absolutePathProvider
	 */
	public function testGetAbsolutePath($expectedPath, $relativePath) {
		$view = new \OC\Files\View('/files');
		$this->assertEquals($expectedPath, $view->getAbsolutePath($relativePath));
	}

	public function testPartFileInfo() {
		$storage = new Temporary(array());
		$scanner = $storage->getScanner();
		\OC\Files\Filesystem::mount($storage, array(), '/test/');
		$storage->file_put_contents('test.part', 'foobar');
		$scanner->scan('');
		$view = new \OC\Files\View('/test');
		$info = $view->getFileInfo('test.part');

		$this->assertInstanceOf('\OCP\Files\FileInfo', $info);
		$this->assertNull($info->getId());
		$this->assertEquals(6, $info->getSize());
	}

	function absolutePathProvider() {
		return array(
			array('/files/', ''),
			array('/files/0', '0'),
			array('/files/false', 'false'),
			array('/files/true', 'true'),
			array('/files/', '/'),
			array('/files/test', 'test'),
			array('/files/test', '/test'),
		);
	}

	/**
	 * @dataProvider chrootRelativePathProvider
	 */
	function testChrootGetRelativePath($root, $absolutePath, $expectedPath) {
		$view = new \OC\Files\View('/files');
		$view->chroot($root);
		$this->assertEquals($expectedPath, $view->getRelativePath($absolutePath));
	}

	public function chrootRelativePathProvider() {
		return $this->relativePathProvider('/');
	}

	/**
	 * @dataProvider initRelativePathProvider
	 */
	public function testInitGetRelativePath($root, $absolutePath, $expectedPath) {
		$view = new \OC\Files\View($root);
		$this->assertEquals($expectedPath, $view->getRelativePath($absolutePath));
	}

	public function initRelativePathProvider() {
		return $this->relativePathProvider(null);
	}

	public function relativePathProvider($missingRootExpectedPath) {
		return array(
			// No root - returns the path
			array('', '/files', '/files'),
			array('', '/files/', '/files/'),

			// Root equals path - /
			array('/files/', '/files/', '/'),
			array('/files/', '/files', '/'),
			array('/files', '/files/', '/'),
			array('/files', '/files', '/'),

			// False negatives: chroot fixes those by adding the leading slash.
			// But setting them up with this root (instead of chroot($root))
			// will fail them, although they should be the same.
			// TODO init should be fixed, so it also adds the leading slash
			array('files/', '/files/', $missingRootExpectedPath),
			array('files', '/files/', $missingRootExpectedPath),
			array('files/', '/files', $missingRootExpectedPath),
			array('files', '/files', $missingRootExpectedPath),

			// False negatives: Paths provided to the method should have a leading slash
			// TODO input should be checked to have a leading slash
			array('/files/', 'files/', null),
			array('/files', 'files/', null),
			array('/files/', 'files', null),
			array('/files', 'files', null),

			// with trailing slashes
			array('/files/', '/files/0', '0'),
			array('/files/', '/files/false', 'false'),
			array('/files/', '/files/true', 'true'),
			array('/files/', '/files/test', 'test'),
			array('/files/', '/files/test/foo', 'test/foo'),

			// without trailing slashes
			// TODO false expectation: Should match "with trailing slashes"
			array('/files', '/files/0', '/0'),
			array('/files', '/files/false', '/false'),
			array('/files', '/files/true', '/true'),
			array('/files', '/files/test', '/test'),
			array('/files', '/files/test/foo', '/test/foo'),

			// leading slashes
			array('/files/', '/files_trashbin/', null),
			array('/files', '/files_trashbin/', null),
			array('/files/', '/files_trashbin', null),
			array('/files', '/files_trashbin', null),

			// no leading slashes
			array('files/', 'files_trashbin/', null),
			array('files', 'files_trashbin/', null),
			array('files/', 'files_trashbin', null),
			array('files', 'files_trashbin', null),

			// mixed leading slashes
			array('files/', '/files_trashbin/', null),
			array('/files/', 'files_trashbin/', null),
			array('files', '/files_trashbin/', null),
			array('/files', 'files_trashbin/', null),
			array('files/', '/files_trashbin', null),
			array('/files/', 'files_trashbin', null),
			array('files', '/files_trashbin', null),
			array('/files', 'files_trashbin', null),

			array('files', 'files_trashbin/test', null),
			array('/files', '/files_trashbin/test', null),
			array('/files', 'files_trashbin/test', null),
		);
	}

	public function testFileView() {
		$storage = new Temporary(array());
		$scanner = $storage->getScanner();
		$storage->file_put_contents('foo.txt', 'bar');
		\OC\Files\Filesystem::mount($storage, array(), '/test/');
		$scanner->scan('');
		$view = new \OC\Files\View('/test/foo.txt');

		$this->assertEquals('bar', $view->file_get_contents(''));
		$fh = tmpfile();
		fwrite($fh, 'foo');
		rewind($fh);
		$view->file_put_contents('', $fh);
		$this->assertEquals('foo', $view->file_get_contents(''));
	}

	/**
	 * @dataProvider tooLongPathDataProvider
	 * @expectedException \OCP\Files\InvalidPathException
	 */
	public function testTooLongPath($operation, $param0 = null) {

		$longPath = '';
		// 4000 is the maximum path length in file_cache.path
		$folderName = 'abcdefghijklmnopqrstuvwxyz012345678901234567890123456789';
		$depth = (4000 / 57);
		foreach (range(0, $depth + 1) as $i) {
			$longPath .= '/' . $folderName;
		}

		$storage = new \OC\Files\Storage\Temporary(array());
		$this->tempStorage = $storage; // for later hard cleanup
		\OC\Files\Filesystem::mount($storage, array(), '/');

		$rootView = new \OC\Files\View('');

		if ($param0 === '@0') {
			$param0 = $longPath;
		}

		if ($operation === 'hash') {
			$param0 = $longPath;
			$longPath = 'md5';
		}

		call_user_func(array($rootView, $operation), $longPath, $param0);
	}

	public function tooLongPathDataProvider() {
		return array(
			array('getAbsolutePath'),
			array('getRelativePath'),
			array('getMountPoint'),
			array('resolvePath'),
			array('getLocalFile'),
			array('getLocalFolder'),
			array('mkdir'),
			array('rmdir'),
			array('opendir'),
			array('is_dir'),
			array('is_file'),
			array('stat'),
			array('filetype'),
			array('filesize'),
			array('readfile'),
			array('isCreatable'),
			array('isReadable'),
			array('isUpdatable'),
			array('isDeletable'),
			array('isSharable'),
			array('file_exists'),
			array('filemtime'),
			array('touch'),
			array('file_get_contents'),
			array('unlink'),
			array('deleteAll'),
			array('toTmpFile'),
			array('getMimeType'),
			array('free_space'),
			array('getFileInfo'),
			array('getDirectoryContent'),
			array('getOwner'),
			array('getETag'),
			array('file_put_contents', 'ipsum'),
			array('rename', '@0'),
			array('copy', '@0'),
			array('fopen', 'r'),
			array('fromTmpFile', '@0'),
			array('hash'),
			array('hasUpdated', 0),
			array('putFileInfo', array()),
		);
	}

	public function testRenameCrossStoragePreserveMtime() {
		$storage1 = new Temporary(array());
		$storage2 = new Temporary(array());
		$scanner1 = $storage1->getScanner();
		$scanner2 = $storage2->getScanner();
		$storage1->mkdir('sub');
		$storage1->mkdir('foo');
		$storage1->file_put_contents('foo.txt', 'asd');
		$storage1->file_put_contents('foo/bar.txt', 'asd');
		\OC\Files\Filesystem::mount($storage1, array(), '/test/');
		\OC\Files\Filesystem::mount($storage2, array(), '/test/sub/storage');

		$view = new \OC\Files\View('');
		$time = time() - 200;
		$view->touch('/test/foo.txt', $time);
		$view->touch('/test/foo', $time);
		$view->touch('/test/foo/bar.txt', $time);

		$view->rename('/test/foo.txt', '/test/sub/storage/foo.txt');

		$this->assertEquals($time, $view->filemtime('/test/sub/storage/foo.txt'));

		$view->rename('/test/foo', '/test/sub/storage/foo');

		$this->assertEquals($time, $view->filemtime('/test/sub/storage/foo/bar.txt'));
	}

	public function testRenameFailDeleteTargetKeepSource() {
		$this->doTestCopyRenameFail('rename');
	}

	public function testCopyFailDeleteTargetKeepSource() {
		$this->doTestCopyRenameFail('copy');
	}

	private function doTestCopyRenameFail($operation) {
		$storage1 = new Temporary(array());
		/** @var \PHPUnit_Framework_MockObject_MockObject | \OC\Files\Storage\Temporary $storage2 */
		$storage2 = $this->getMockBuilder('\Test\Files\TemporaryNoCross')
			->setConstructorArgs([[]])
			->setMethods(['fopen'])
			->getMock();

		$storage2->expects($this->any())
			->method('fopen')
			->will($this->returnCallback(function ($path, $mode) use ($storage2) {
				/** @var \PHPUnit_Framework_MockObject_MockObject | \OC\Files\Storage\Temporary $storage2 */
				$source = fopen($storage2->getSourcePath($path), $mode);
				return \OC\Files\Stream\Quota::wrap($source, 9);
			}));

		$storage1->mkdir('sub');
		$storage1->file_put_contents('foo.txt', '0123456789ABCDEFGH');
		$storage1->mkdir('dirtomove');
		$storage1->file_put_contents('dirtomove/indir1.txt', '0123456'); // fits
		$storage1->file_put_contents('dirtomove/indir2.txt', '0123456789ABCDEFGH'); // doesn't fit
		$storage2->file_put_contents('existing.txt', '0123');
		$storage1->getScanner()->scan('');
		$storage2->getScanner()->scan('');
		\OC\Files\Filesystem::mount($storage1, array(), '/test/');
		\OC\Files\Filesystem::mount($storage2, array(), '/test/sub/storage');

		// move file
		$view = new \OC\Files\View('');
		$this->assertTrue($storage1->file_exists('foo.txt'));
		$this->assertFalse($storage2->file_exists('foo.txt'));
		$this->assertFalse($view->$operation('/test/foo.txt', '/test/sub/storage/foo.txt'));
		$this->assertFalse($storage2->file_exists('foo.txt'));
		$this->assertFalse($storage2->getCache()->get('foo.txt'));
		$this->assertTrue($storage1->file_exists('foo.txt'));

		// if target exists, it will be deleted too
		$this->assertFalse($view->$operation('/test/foo.txt', '/test/sub/storage/existing.txt'));
		$this->assertFalse($storage2->file_exists('existing.txt'));
		$this->assertFalse($storage2->getCache()->get('existing.txt'));
		$this->assertTrue($storage1->file_exists('foo.txt'));

		// move folder
		$this->assertFalse($view->$operation('/test/dirtomove/', '/test/sub/storage/dirtomove/'));
		// since the move failed, the full source tree is kept
		$this->assertTrue($storage1->file_exists('dirtomove/indir1.txt'));
		$this->assertTrue($storage1->file_exists('dirtomove/indir2.txt'));
		// second file not moved/copied
		$this->assertFalse($storage2->file_exists('dirtomove/indir2.txt'));
		$this->assertFalse($storage2->getCache()->get('dirtomove/indir2.txt'));

	}

	public function testDeleteFailKeepCache() {
		/**
		 * @var \PHPUnit_Framework_MockObject_MockObject | \OC\Files\Storage\Temporary $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setConstructorArgs(array(array()))
			->setMethods(array('unlink'))
			->getMock();
		$storage->expects($this->once())
			->method('unlink')
			->will($this->returnValue(false));
		$scanner = $storage->getScanner();
		$cache = $storage->getCache();
		$storage->file_put_contents('foo.txt', 'asd');
		$scanner->scan('');
		\OC\Files\Filesystem::mount($storage, array(), '/test/');

		$view = new \OC\Files\View('/test');

		$this->assertFalse($view->unlink('foo.txt'));
		$this->assertTrue($cache->inCache('foo.txt'));
	}

	function directoryTraversalProvider() {
		return [
			['../test/'],
			['..\\test\\my/../folder'],
			['/test/my/../foo\\'],
		];
	}

	/**
	 * @dataProvider directoryTraversalProvider
	 * @expectedException \Exception
	 * @param string $root
	 */
	public function testConstructDirectoryTraversalException($root) {
		new \OC\Files\View($root);
	}

	public function testRenameOverWrite() {
		$storage = new Temporary(array());
		$scanner = $storage->getScanner();
		$storage->mkdir('sub');
		$storage->mkdir('foo');
		$storage->file_put_contents('foo.txt', 'asd');
		$storage->file_put_contents('foo/bar.txt', 'asd');
		$scanner->scan('');
		\OC\Files\Filesystem::mount($storage, array(), '/test/');
		$view = new \OC\Files\View('');
		$this->assertTrue($view->rename('/test/foo.txt', '/test/foo/bar.txt'));
	}

	public function testSetMountOptionsInStorage() {
		$mount = new MountPoint('\OC\Files\Storage\Temporary', '/asd/', [[]], \OC\Files\Filesystem::getLoader(), ['foo' => 'bar']);
		\OC\Files\Filesystem::getMountManager()->addMount($mount);
		/** @var \OC\Files\Storage\Common $storage */
		$storage = $mount->getStorage();
		$this->assertEquals($storage->getMountOption('foo'), 'bar');
	}

	public function testSetMountOptionsWatcherPolicy() {
		$mount = new MountPoint('\OC\Files\Storage\Temporary', '/asd/', [[]], \OC\Files\Filesystem::getLoader(), ['filesystem_check_changes' => Watcher::CHECK_NEVER]);
		\OC\Files\Filesystem::getMountManager()->addMount($mount);
		/** @var \OC\Files\Storage\Common $storage */
		$storage = $mount->getStorage();
		$watcher = $storage->getWatcher();
		$this->assertEquals(Watcher::CHECK_NEVER, $watcher->getPolicy());
	}

	public function testGetAbsolutePathOnNull() {
		$view = new \OC\Files\View();
		$this->assertNull($view->getAbsolutePath(null));
	}

	public function testGetRelativePathOnNull() {
		$view = new \OC\Files\View();
		$this->assertNull($view->getRelativePath(null));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testNullAsRoot() {
		new \OC\Files\View(null);
	}

	/**
	 * e.g. reading from a folder that's being renamed
	 *
	 * @expectedException \OCP\Lock\LockedException
	 *
	 * @dataProvider dataLockPaths
	 *
	 * @param string $rootPath
	 * @param string $pathPrefix
	 */
	public function testReadFromWriteLockedPath($rootPath, $pathPrefix) {
		$rootPath = str_replace('{folder}', 'files', $rootPath);
		$pathPrefix = str_replace('{folder}', 'files', $pathPrefix);

		$view = new \OC\Files\View($rootPath);
		$storage = new Temporary(array());
		\OC\Files\Filesystem::mount($storage, [], '/');
		$this->assertTrue($view->lockFile($pathPrefix . '/foo/bar', ILockingProvider::LOCK_EXCLUSIVE));
		$view->lockFile($pathPrefix . '/foo/bar/asd', ILockingProvider::LOCK_SHARED);
	}

	/**
	 * Reading from a files_encryption folder that's being renamed
	 *
	 * @dataProvider dataLockPaths
	 *
	 * @param string $rootPath
	 * @param string $pathPrefix
	 */
	public function testReadFromWriteUnlockablePath($rootPath, $pathPrefix) {
		$rootPath = str_replace('{folder}', 'files_encryption', $rootPath);
		$pathPrefix = str_replace('{folder}', 'files_encryption', $pathPrefix);

		$view = new \OC\Files\View($rootPath);
		$storage = new Temporary(array());
		\OC\Files\Filesystem::mount($storage, [], '/');
		$this->assertFalse($view->lockFile($pathPrefix . '/foo/bar', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($view->lockFile($pathPrefix . '/foo/bar/asd', ILockingProvider::LOCK_SHARED));
	}

	/**
	 * e.g. writing a file that's being downloaded
	 *
	 * @expectedException \OCP\Lock\LockedException
	 *
	 * @dataProvider dataLockPaths
	 *
	 * @param string $rootPath
	 * @param string $pathPrefix
	 */
	public function testWriteToReadLockedFile($rootPath, $pathPrefix) {
		$rootPath = str_replace('{folder}', 'files', $rootPath);
		$pathPrefix = str_replace('{folder}', 'files', $pathPrefix);

		$view = new \OC\Files\View($rootPath);
		$storage = new Temporary(array());
		\OC\Files\Filesystem::mount($storage, [], '/');
		$this->assertTrue($view->lockFile($pathPrefix . '/foo/bar', ILockingProvider::LOCK_SHARED));
		$view->lockFile($pathPrefix . '/foo/bar', ILockingProvider::LOCK_EXCLUSIVE);
	}

	/**
	 * Writing a file that's being downloaded
	 *
	 * @dataProvider dataLockPaths
	 *
	 * @param string $rootPath
	 * @param string $pathPrefix
	 */
	public function testWriteToReadUnlockableFile($rootPath, $pathPrefix) {
		$rootPath = str_replace('{folder}', 'files_encryption', $rootPath);
		$pathPrefix = str_replace('{folder}', 'files_encryption', $pathPrefix);

		$view = new \OC\Files\View($rootPath);
		$storage = new Temporary(array());
		\OC\Files\Filesystem::mount($storage, [], '/');
		$this->assertFalse($view->lockFile($pathPrefix . '/foo/bar', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($view->lockFile($pathPrefix . '/foo/bar', ILockingProvider::LOCK_EXCLUSIVE));
	}

	/**
	 * Test that locks are on mount point paths instead of mount root
	 */
	public function testLockLocalMountPointPathInsteadOfStorageRoot() {
		$lockingProvider = \OC::$server->getLockingProvider();
		$view = new \OC\Files\View('/testuser/files/');
		$storage = new Temporary([]);
		\OC\Files\Filesystem::mount($storage, [], '/');
		$mountedStorage = new Temporary([]);
		\OC\Files\Filesystem::mount($mountedStorage, [], '/testuser/files/mountpoint');

		$this->assertTrue(
			$view->lockFile('/mountpoint', ILockingProvider::LOCK_EXCLUSIVE, true),
			'Can lock mount point'
		);

		// no exception here because storage root was not locked
		$mountedStorage->acquireLock('', ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);

		$thrown = false;
		try {
			$storage->acquireLock('/testuser/files/mountpoint', ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);
		} catch (\OCP\Lock\LockedException $e) {
			$thrown = true;
		}
		$this->assertTrue($thrown, 'Mount point path was locked on root storage');

		$lockingProvider->releaseAll();
	}

	/**
	 * Test that locks are on mount point paths and also mount root when requested
	 */
	public function testLockStorageRootButNotLocalMountPoint() {
		$lockingProvider = \OC::$server->getLockingProvider();
		$view = new \OC\Files\View('/testuser/files/');
		$storage = new Temporary([]);
		\OC\Files\Filesystem::mount($storage, [], '/');
		$mountedStorage = new Temporary([]);
		\OC\Files\Filesystem::mount($mountedStorage, [], '/testuser/files/mountpoint');

		$this->assertTrue(
			$view->lockFile('/mountpoint', ILockingProvider::LOCK_EXCLUSIVE, false),
			'Can lock mount point'
		);

		$thrown = false;
		try {
			$mountedStorage->acquireLock('', ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);
		} catch (\OCP\Lock\LockedException $e) {
			$thrown = true;
		}
		$this->assertTrue($thrown, 'Mount point storage root was locked on original storage');

		// local mount point was not locked
		$storage->acquireLock('/testuser/files/mountpoint', ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);

		$lockingProvider->releaseAll();
	}

	/**
	 * Test that locks are on mount point paths and also mount root when requested
	 */
	public function testLockMountPointPathFailReleasesBoth() {
		$lockingProvider = \OC::$server->getLockingProvider();
		$view = new \OC\Files\View('/testuser/files/');
		$storage = new Temporary([]);
		\OC\Files\Filesystem::mount($storage, [], '/');
		$mountedStorage = new Temporary([]);
		\OC\Files\Filesystem::mount($mountedStorage, [], '/testuser/files/mountpoint.txt');

		// this would happen if someone is writing on the mount point
		$mountedStorage->acquireLock('', ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);

		$thrown = false;
		try {
			// this actually acquires two locks, one on the mount point and one on the storage root,
			// but the one on the storage root will fail
			$view->lockFile('/mountpoint.txt', ILockingProvider::LOCK_SHARED);
		} catch (\OCP\Lock\LockedException $e) {
			$thrown = true;
		}
		$this->assertTrue($thrown, 'Cannot acquire shared lock because storage root is already locked');

		// from here we expect that the lock on the local mount point was released properly
		// so acquiring an exclusive lock will succeed
		$storage->acquireLock('/testuser/files/mountpoint.txt', ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);

		$lockingProvider->releaseAll();
	}

	public function dataLockPaths() {
		return [
			['/testuser/{folder}', ''],
			['/testuser', '/{folder}'],
			['', '/testuser/{folder}'],
		];
	}

	public function pathRelativeToFilesProvider() {
		return [
			['admin/files', ''],
			['admin/files/x', 'x'],
			['/admin/files', ''],
			['/admin/files/sub', 'sub'],
			['/admin/files/sub/', 'sub'],
			['/admin/files/sub/sub2', 'sub/sub2'],
			['//admin//files/sub//sub2', 'sub/sub2'],
		];
	}

	/**
	 * @dataProvider pathRelativeToFilesProvider
	 */
	public function testGetPathRelativeToFiles($path, $expectedPath) {
		$view = new \OC\Files\View();
		$this->assertEquals($expectedPath, $view->getPathRelativeToFiles($path));
	}

	public function pathRelativeToFilesProviderExceptionCases() {
		return [
			[''],
			['x'],
			['files'],
			['/files'],
			['/admin/files_versions/abc'],
		];
	}

	/**
	 * @dataProvider pathRelativeToFilesProviderExceptionCases
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetPathRelativeToFilesWithInvalidArgument($path) {
		$view = new \OC\Files\View();
		$view->getPathRelativeToFiles($path);
	}

	public function testChangeLock() {
		$view = new \OC\Files\View('/testuser/files/');
		$storage = new Temporary(array());
		\OC\Files\Filesystem::mount($storage, [], '/');

		$view->lockFile('/test/sub', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->isFileLocked($view, '/test//sub', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->isFileLocked($view, '/test//sub', ILockingProvider::LOCK_EXCLUSIVE));

		$view->changeLock('//test/sub', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->isFileLocked($view, '/test//sub', ILockingProvider::LOCK_EXCLUSIVE));

		$view->changeLock('test/sub', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->isFileLocked($view, '/test//sub', ILockingProvider::LOCK_SHARED));

		$view->unlockFile('/test/sub/', ILockingProvider::LOCK_SHARED);

		$this->assertFalse($this->isFileLocked($view, '/test//sub', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->isFileLocked($view, '/test//sub', ILockingProvider::LOCK_EXCLUSIVE));

	}

	public function hookPathProvider() {
		return [
			['/foo/files', '/foo', true],
			['/foo/files/bar', '/foo', true],
			['/foo', '/foo', false],
			['/foo', '/files/foo', true],
			['/foo', 'filesfoo', false],
			['', '/foo/files', true],
			['', '/foo/files/bar.txt', true]
		];
	}

	/**
	 * @dataProvider hookPathProvider
	 * @param $root
	 * @param $path
	 * @param $shouldEmit
	 */
	public function testHookPaths($root, $path, $shouldEmit) {
		$filesystemReflection = new \ReflectionClass('\OC\Files\Filesystem');
		$defaultRootValue = $filesystemReflection->getProperty('defaultInstance');
		$defaultRootValue->setAccessible(true);
		$oldRoot = $defaultRootValue->getValue();
		$defaultView = new \OC\Files\View('/foo/files');
		$defaultRootValue->setValue($defaultView);
		$view = new \OC\Files\View($root);
		$result = $this->invokePrivate($view, 'shouldEmitHooks', [$path]);
		$defaultRootValue->setValue($oldRoot);
		$this->assertEquals($shouldEmit, $result);
	}

	/**
	 * Create test movable mount points
	 *
	 * @param array $mountPoints array of mount point locations
	 * @return array array of MountPoint objects
	 */
	private function createTestMovableMountPoints($mountPoints) {
		$mounts = [];
		foreach ($mountPoints as $mountPoint) {
			$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
				->setMethods([])
				->getMock();

			$mounts[] = $this->getMock(
				'\Test\TestMoveableMountPoint',
				['moveMount'],
				[$storage, $mountPoint]
			);
		}

		$mountProvider = $this->getMock('\OCP\Files\Config\IMountProvider');
		$mountProvider->expects($this->any())
			->method('getMountsForUser')
			->will($this->returnValue($mounts));

		$mountProviderCollection = \OC::$server->getMountProviderCollection();
		$mountProviderCollection->registerProvider($mountProvider);

		return $mounts;
	}

	/**
	 * Test mount point move
	 */
	public function testMountPointMove() {
		$this->loginAsUser($this->user);

		list($mount1, $mount2) = $this->createTestMovableMountPoints([
			$this->user . '/files/mount1',
			$this->user . '/files/mount2',
		]);
		$mount1->expects($this->once())
			->method('moveMount')
			->will($this->returnValue(true));

		$mount2->expects($this->once())
			->method('moveMount')
			->will($this->returnValue(true));

		$view = new \OC\Files\View('/' . $this->user . '/files/');
		$view->mkdir('sub');

		$this->assertTrue($view->rename('mount1', 'renamed_mount'), 'Can rename mount point');
		$this->assertTrue($view->rename('mount2', 'sub/moved_mount'), 'Can move a mount point into a subdirectory');
	}

	/**
	 * Test that moving a mount point into another is forbidden
	 */
	public function testMoveMountPointIntoAnother() {
		$this->loginAsUser($this->user);

		list($mount1, $mount2) = $this->createTestMovableMountPoints([
			$this->user . '/files/mount1',
			$this->user . '/files/mount2',
		]);

		$mount1->expects($this->never())
			->method('moveMount');

		$mount2->expects($this->never())
			->method('moveMount');

		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$this->assertFalse($view->rename('mount1', 'mount2'), 'Cannot overwrite another mount point');
		$this->assertFalse($view->rename('mount1', 'mount2/sub'), 'Cannot move a mount point into another');
	}

	/**
	 * Test that moving a mount point into a shared folder is forbidden
	 */
	public function testMoveMountPointIntoSharedFolder() {
		$this->loginAsUser($this->user);

		list($mount1) = $this->createTestMovableMountPoints([
			$this->user . '/files/mount1',
		]);

		$mount1->expects($this->never())
			->method('moveMount');

		$view = new \OC\Files\View('/' . $this->user . '/files/');
		$view->mkdir('shareddir');
		$view->mkdir('shareddir/sub');
		$view->mkdir('shareddir/sub2');

		$fileId = $view->getFileInfo('shareddir')->getId();
		$userObject = \OC::$server->getUserManager()->createUser('test2', 'IHateNonMockableStaticClasses');
		$this->assertTrue(\OCP\Share::shareItem('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, 'test2', \OCP\Constants::PERMISSION_READ));

		$this->assertFalse($view->rename('mount1', 'shareddir'), 'Cannot overwrite shared folder');
		$this->assertFalse($view->rename('mount1', 'shareddir/sub'), 'Cannot move mount point into shared folder');
		$this->assertFalse($view->rename('mount1', 'shareddir/sub/sub2'), 'Cannot move mount point into shared subfolder');

		$this->assertTrue(\OCP\Share::unshare('folder', $fileId, \OCP\Share::SHARE_TYPE_USER, 'test2'));
		$userObject->delete();
	}

	public function basicOperationProviderForLocks() {
		return [
			// --- write hook ----
			[
				'touch',
				['touch-create.txt'],
				'touch-create.txt',
				'create',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_EXCLUSIVE,
				ILockingProvider::LOCK_SHARED,
			],
			[
				'fopen',
				['test-write.txt', 'w'],
				'test-write.txt',
				'write',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_EXCLUSIVE,
				null,
				// exclusive lock stays until fclose
				ILockingProvider::LOCK_EXCLUSIVE,
			],
			[
				'mkdir',
				['newdir'],
				'newdir',
				'write',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_EXCLUSIVE,
				ILockingProvider::LOCK_SHARED,
			],
			[
				'file_put_contents',
				['file_put_contents.txt', 'blah'],
				'file_put_contents.txt',
				'write',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_EXCLUSIVE,
				ILockingProvider::LOCK_SHARED,
			],

			// ---- delete hook ----
			[
				'rmdir',
				['dir'],
				'dir',
				'delete',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_EXCLUSIVE,
				ILockingProvider::LOCK_SHARED,
			],
			[
				'unlink',
				['test.txt'],
				'test.txt',
				'delete',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_EXCLUSIVE,
				ILockingProvider::LOCK_SHARED,
			],

			// ---- read hook (no post hooks) ----
			[
				'file_get_contents',
				['test.txt'],
				'test.txt',
				'read',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_SHARED,
				null,
			],
			[
				'fopen',
				['test.txt', 'r'],
				'test.txt',
				'read',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_SHARED,
				null,
			],
			[
				'opendir',
				['dir'],
				'dir',
				'read',
				ILockingProvider::LOCK_SHARED,
				ILockingProvider::LOCK_SHARED,
				null,
			],

			// ---- no lock, touch hook ---
			['touch', ['test.txt'], 'test.txt', 'touch', null, null, null],

			// ---- no hooks, no locks ---
			['is_dir', ['dir'], 'dir', null],
			['is_file', ['dir'], 'dir', null],
			['stat', ['dir'], 'dir', null],
			['filetype', ['dir'], 'dir', null],
			['filesize', ['dir'], 'dir', null],
			['isCreatable', ['dir'], 'dir', null],
			['isReadable', ['dir'], 'dir', null],
			['isUpdatable', ['dir'], 'dir', null],
			['isDeletable', ['dir'], 'dir', null],
			['isSharable', ['dir'], 'dir', null],
			['file_exists', ['dir'], 'dir', null],
			['filemtime', ['dir'], 'dir', null],
		];
	}

	/**
	 * Test whether locks are set before and after the operation
	 *
	 * @dataProvider basicOperationProviderForLocks
	 *
	 * @param string $operation operation name on the view
	 * @param array $operationArgs arguments for the operation
	 * @param string $lockedPath path of the locked item to check
	 * @param string $hookType hook type
	 * @param int $expectedLockBefore expected lock during pre hooks
	 * @param int $expectedLockduring expected lock during operation
	 * @param int $expectedLockAfter expected lock during post hooks
	 * @param int $expectedStrayLock expected lock after returning, should
	 * be null (unlock) for most operations
	 */
	public function testLockBasicOperation(
		$operation,
		$operationArgs,
		$lockedPath,
		$hookType,
		$expectedLockBefore = ILockingProvider::LOCK_SHARED,
		$expectedLockDuring = ILockingProvider::LOCK_SHARED,
		$expectedLockAfter = ILockingProvider::LOCK_SHARED,
		$expectedStrayLock = null
	) {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods([$operation])
			->getMock();

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');

		// work directly on disk because mkdir might be mocked
		$realPath = $storage->getSourcePath('');
		mkdir($realPath . '/files');
		mkdir($realPath . '/files/dir');
		file_put_contents($realPath . '/files/test.txt', 'blah');
		$storage->getScanner()->scan('files');

		$storage->expects($this->once())
			->method($operation)
			->will($this->returnCallback(
				function () use ($view, $lockedPath, &$lockTypeDuring) {
					$lockTypeDuring = $this->getFileLockType($view, $lockedPath);

					return true;
				}
			));

		$this->assertNull($this->getFileLockType($view, $lockedPath), 'File not locked before operation');

		$this->connectMockHooks($hookType, $view, $lockedPath, $lockTypePre, $lockTypePost);

		// do operation
		call_user_func_array(array($view, $operation), $operationArgs);

		if ($hookType !== null) {
			$this->assertEquals($expectedLockBefore, $lockTypePre, 'File locked properly during pre-hook');
			$this->assertEquals($expectedLockAfter, $lockTypePost, 'File locked properly during post-hook');
			$this->assertEquals($expectedLockDuring, $lockTypeDuring, 'File locked properly during operation');
		} else {
			$this->assertNull($lockTypeDuring, 'File not locked during operation');
		}

		$this->assertEquals($expectedStrayLock, $this->getFileLockType($view, $lockedPath));
	}

	/**
	 * Test locks for file_put_content with stream.
	 * This code path uses $storage->fopen instead
	 */
	public function testLockFilePutContentWithStream() {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$path = 'test_file_put_contents.txt';
		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods(['fopen'])
			->getMock();

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');
		$storage->mkdir('files');

		$storage->expects($this->once())
			->method('fopen')
			->will($this->returnCallback(
				function () use ($view, $path, &$lockTypeDuring) {
					$lockTypeDuring = $this->getFileLockType($view, $path);

					return fopen('php://temp', 'r+');
				}
			));

		$this->connectMockHooks('write', $view, $path, $lockTypePre, $lockTypePost);

		$this->assertNull($this->getFileLockType($view, $path), 'File not locked before operation');

		// do operation
		$view->file_put_contents($path, fopen('php://temp', 'r+'));

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypePre, 'File locked properly during pre-hook');
		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypePost, 'File locked properly during post-hook');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $lockTypeDuring, 'File locked properly during operation');

		$this->assertNull($this->getFileLockType($view, $path));
	}

	/**
	 * Test locks for fopen with fclose at the end
	 */
	public function testLockFopen() {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$path = 'test_file_put_contents.txt';
		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods(['fopen'])
			->getMock();

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');
		$storage->mkdir('files');

		$storage->expects($this->once())
			->method('fopen')
			->will($this->returnCallback(
				function () use ($view, $path, &$lockTypeDuring) {
					$lockTypeDuring = $this->getFileLockType($view, $path);

					return fopen('php://temp', 'r+');
				}
			));

		$this->connectMockHooks('write', $view, $path, $lockTypePre, $lockTypePost);

		$this->assertNull($this->getFileLockType($view, $path), 'File not locked before operation');

		// do operation
		$res = $view->fopen($path, 'w');

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypePre, 'File locked properly during pre-hook');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $lockTypeDuring, 'File locked properly during operation');
		$this->assertNull($lockTypePost, 'No post hook, no lock check possible');

		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $lockTypeDuring, 'File still locked after fopen');

		fclose($res);

		$this->assertNull($this->getFileLockType($view, $path), 'File unlocked after fclose');
	}

	/**
	 * Test locks for fopen with fclose at the end
	 *
	 * @dataProvider basicOperationProviderForLocks
	 *
	 * @param string $operation operation name on the view
	 * @param array $operationArgs arguments for the operation
	 * @param string $path path of the locked item to check
	 */
	public function testLockBasicOperationUnlocksAfterException(
		$operation,
		$operationArgs,
		$path
	) {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods([$operation])
			->getMock();

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');

		// work directly on disk because mkdir might be mocked
		$realPath = $storage->getSourcePath('');
		mkdir($realPath . '/files');
		mkdir($realPath . '/files/dir');
		file_put_contents($realPath . '/files/test.txt', 'blah');
		$storage->getScanner()->scan('files');

		$storage->expects($this->once())
			->method($operation)
			->will($this->returnCallback(
				function () {
					throw new \Exception('Simulated exception');
				}
			));

		$thrown = false;
		try {
			call_user_func_array(array($view, $operation), $operationArgs);
		} catch (\Exception $e) {
			$thrown = true;
			$this->assertEquals('Simulated exception', $e->getMessage());
		}
		$this->assertTrue($thrown, 'Exception was rethrown');
		$this->assertNull($this->getFileLockType($view, $path), 'File got unlocked after exception');
	}

	/**
	 * Test locks for fopen with fclose at the end
	 *
	 * @dataProvider basicOperationProviderForLocks
	 *
	 * @param string $operation operation name on the view
	 * @param array $operationArgs arguments for the operation
	 * @param string $path path of the locked item to check
	 * @param string $hookType hook type
	 */
	public function testLockBasicOperationUnlocksAfterCancelledHook(
		$operation,
		$operationArgs,
		$path,
		$hookType
	) {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods([$operation])
			->getMock();

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');
		$storage->mkdir('files');

		\OCP\Util::connectHook(
			\OC\Files\Filesystem::CLASSNAME,
			$hookType,
			'\Test\HookHelper',
			'cancellingCallback'
		);

		call_user_func_array(array($view, $operation), $operationArgs);

		$this->assertNull($this->getFileLockType($view, $path), 'File got unlocked after exception');
	}

	public function lockFileRenameOrCopyDataProvider() {
		return [
			['rename', ILockingProvider::LOCK_EXCLUSIVE],
			['copy', ILockingProvider::LOCK_SHARED],
		];
	}

	/**
	 * Test locks for rename or copy operation
	 *
	 * @dataProvider lockFileRenameOrCopyDataProvider
	 *
	 * @param string $operation operation to be done on the view
	 * @param int $expectedLockTypeSourceDuring expected lock type on source file during
	 * the operation
	 */
	public function testLockFileRename($operation, $expectedLockTypeSourceDuring) {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods([$operation, 'filemtime'])
			->getMock();

		$storage->expects($this->any())
			->method('filemtime')
			->will($this->returnValue(123456789));

		$sourcePath = 'original.txt';
		$targetPath = 'target.txt';

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');
		$storage->mkdir('files');
		$view->file_put_contents($sourcePath, 'meh');

		$storage->expects($this->once())
			->method($operation)
			->will($this->returnCallback(
				function () use ($view, $sourcePath, $targetPath, &$lockTypeSourceDuring, &$lockTypeTargetDuring) {
					$lockTypeSourceDuring = $this->getFileLockType($view, $sourcePath);
					$lockTypeTargetDuring = $this->getFileLockType($view, $targetPath);

					return true;
				}
			));

		$this->connectMockHooks($operation, $view, $sourcePath, $lockTypeSourcePre, $lockTypeSourcePost);
		$this->connectMockHooks($operation, $view, $targetPath, $lockTypeTargetPre, $lockTypeTargetPost);

		$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked before operation');
		$this->assertNull($this->getFileLockType($view, $targetPath), 'Target file not locked before operation');

		$view->$operation($sourcePath, $targetPath);

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeSourcePre, 'Source file locked properly during pre-hook');
		$this->assertEquals($expectedLockTypeSourceDuring, $lockTypeSourceDuring, 'Source file locked properly during operation');
		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeSourcePost, 'Source file locked properly during post-hook');

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeTargetPre, 'Target file locked properly during pre-hook');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $lockTypeTargetDuring, 'Target file locked properly during operation');
		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeTargetPost, 'Target file locked properly during post-hook');

		$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked after operation');
		$this->assertNull($this->getFileLockType($view, $targetPath), 'Target file not locked after operation');
	}

	/**
	 * simulate a failed copy operation.
	 * We expect that we catch the exception, free the lock and re-throw it.
	 *
	 * @expectedException \Exception
	 */
	public function testLockFileCopyException() {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods(['copy'])
			->getMock();

		$sourcePath = 'original.txt';
		$targetPath = 'target.txt';

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');
		$storage->mkdir('files');
		$view->file_put_contents($sourcePath, 'meh');

		$storage->expects($this->once())
			->method('copy')
			->will($this->returnCallback(
				function () {
					throw new \Exception();
				}
			));

		$this->connectMockHooks('copy', $view, $sourcePath, $lockTypeSourcePre, $lockTypeSourcePost);
		$this->connectMockHooks('copy', $view, $targetPath, $lockTypeTargetPre, $lockTypeTargetPost);

		$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked before operation');
		$this->assertNull($this->getFileLockType($view, $targetPath), 'Target file not locked before operation');

		try {
			$view->copy($sourcePath, $targetPath);
		} catch (\Exception $e) {
			$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked after operation');
			$this->assertNull($this->getFileLockType($view, $targetPath), 'Target file not locked after operation');
			throw $e;
		}
	}

	/**
	 * Test rename operation: unlock first path when second path was locked
	 */
	public function testLockFileRenameUnlockOnException() {
		$this->loginAsUser('test');

		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$sourcePath = 'original.txt';
		$targetPath = 'target.txt';
		$view->file_put_contents($sourcePath, 'meh');

		// simulate that the target path is already locked
		$view->lockFile($targetPath, ILockingProvider::LOCK_EXCLUSIVE);

		$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked before operation');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $this->getFileLockType($view, $targetPath), 'Target file is locked before operation');

		$thrown = false;
		try {
			$view->rename($sourcePath, $targetPath);
		} catch (\OCP\Lock\LockedException $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown, 'LockedException thrown');

		$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked after operation');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $this->getFileLockType($view, $targetPath), 'Target file still locked after operation');

		$view->unlockFile($targetPath, ILockingProvider::LOCK_EXCLUSIVE);
	}

	/**
	 * Test rename operation: unlock first path when second path was locked
	 */
	public function testGetOwner() {
		$this->loginAsUser('test');

		$view = new \OC\Files\View('/test/files/');

		$path = 'foo.txt';
		$view->file_put_contents($path, 'meh');

		$this->assertEquals('test', $view->getFileInfo($path)->getOwner()->getUID());

		$folderInfo = $view->getDirectoryContent('');
		$folderInfo = array_values(array_filter($folderInfo, function (FileInfo $info) {
			return $info->getName() === 'foo.txt';
		}));

		$this->assertEquals('test', $folderInfo[0]->getOwner()->getUID());

		$subStorage = new Temporary();
		\OC\Files\Filesystem::mount($subStorage, [], '/test/files/asd');

		$folderInfo = $view->getDirectoryContent('');
		$folderInfo = array_values(array_filter($folderInfo, function (FileInfo $info) {
			return $info->getName() === 'asd';
		}));

		$this->assertEquals('test', $folderInfo[0]->getOwner()->getUID());
	}

	public function lockFileRenameOrCopyCrossStorageDataProvider() {
		return [
			['rename', 'moveFromStorage', ILockingProvider::LOCK_EXCLUSIVE],
			['copy', 'copyFromStorage', ILockingProvider::LOCK_SHARED],
		];
	}

	/**
	 * Test locks for rename or copy operation cross-storage
	 *
	 * @dataProvider lockFileRenameOrCopyCrossStorageDataProvider
	 *
	 * @param string $viewOperation operation to be done on the view
	 * @param string $storageOperation operation to be mocked on the storage
	 * @param int $expectedLockTypeSourceDuring expected lock type on source file during
	 * the operation
	 */
	public function testLockFileRenameCrossStorage($viewOperation, $storageOperation, $expectedLockTypeSourceDuring) {
		$view = new \OC\Files\View('/' . $this->user . '/files/');

		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods([$storageOperation])
			->getMock();
		$storage2 = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->setMethods([$storageOperation, 'filemtime'])
			->getMock();

		$storage2->expects($this->any())
			->method('filemtime')
			->will($this->returnValue(123456789));

		$sourcePath = 'original.txt';
		$targetPath = 'substorage/target.txt';

		\OC\Files\Filesystem::mount($storage, array(), $this->user . '/');
		\OC\Files\Filesystem::mount($storage2, array(), $this->user . '/files/substorage');
		$storage->mkdir('files');
		$view->file_put_contents($sourcePath, 'meh');

		$storage->expects($this->never())
			->method($storageOperation);
		$storage2->expects($this->once())
			->method($storageOperation)
			->will($this->returnCallback(
				function () use ($view, $sourcePath, $targetPath, &$lockTypeSourceDuring, &$lockTypeTargetDuring) {
					$lockTypeSourceDuring = $this->getFileLockType($view, $sourcePath);
					$lockTypeTargetDuring = $this->getFileLockType($view, $targetPath);

					return true;
				}
			));

		$this->connectMockHooks($viewOperation, $view, $sourcePath, $lockTypeSourcePre, $lockTypeSourcePost);
		$this->connectMockHooks($viewOperation, $view, $targetPath, $lockTypeTargetPre, $lockTypeTargetPost);

		$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked before operation');
		$this->assertNull($this->getFileLockType($view, $targetPath), 'Target file not locked before operation');

		$view->$viewOperation($sourcePath, $targetPath);

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeSourcePre, 'Source file locked properly during pre-hook');
		$this->assertEquals($expectedLockTypeSourceDuring, $lockTypeSourceDuring, 'Source file locked properly during operation');
		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeSourcePost, 'Source file locked properly during post-hook');

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeTargetPre, 'Target file locked properly during pre-hook');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $lockTypeTargetDuring, 'Target file locked properly during operation');
		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeTargetPost, 'Target file locked properly during post-hook');

		$this->assertNull($this->getFileLockType($view, $sourcePath), 'Source file not locked after operation');
		$this->assertNull($this->getFileLockType($view, $targetPath), 'Target file not locked after operation');
	}

	/**
	 * Test locks when moving a mount point
	 */
	public function testLockMoveMountPoint() {
		$this->loginAsUser('test');

		list($mount) = $this->createTestMovableMountPoints([
			$this->user . '/files/substorage',
		]);

		$view = new \OC\Files\View('/' . $this->user . '/files/');
		$view->mkdir('subdir');

		$sourcePath = 'substorage';
		$targetPath = 'subdir/substorage_moved';

		$mount->expects($this->once())
			->method('moveMount')
			->will($this->returnCallback(
				function ($target) use ($mount, $view, $sourcePath, $targetPath, &$lockTypeSourceDuring, &$lockTypeTargetDuring, &$lockTypeSharedRootDuring) {
					$lockTypeSourceDuring = $this->getFileLockType($view, $sourcePath, true);
					$lockTypeTargetDuring = $this->getFileLockType($view, $targetPath, true);

					$lockTypeSharedRootDuring = $this->getFileLockType($view, $sourcePath, false);

					$mount->setMountPoint($target);

					return true;
				}
			));

		$this->connectMockHooks('rename', $view, $sourcePath, $lockTypeSourcePre, $lockTypeSourcePost, true);
		$this->connectMockHooks('rename', $view, $targetPath, $lockTypeTargetPre, $lockTypeTargetPost, true);
		// in pre-hook, mount point is still on $sourcePath
		$this->connectMockHooks('rename', $view, $sourcePath, $lockTypeSharedRootPre, $dummy, false);
		// in post-hook, mount point is now on $targetPath
		$this->connectMockHooks('rename', $view, $targetPath, $dummy, $lockTypeSharedRootPost, false);

		$this->assertNull($this->getFileLockType($view, $sourcePath, false), 'Shared storage root not locked before operation');
		$this->assertNull($this->getFileLockType($view, $sourcePath, true), 'Source path not locked before operation');
		$this->assertNull($this->getFileLockType($view, $targetPath, true), 'Target path not locked before operation');

		$view->rename($sourcePath, $targetPath);

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeSourcePre, 'Source path locked properly during pre-hook');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $lockTypeSourceDuring, 'Source path locked properly during operation');
		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeSourcePost, 'Source path locked properly during post-hook');

		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeTargetPre, 'Target path locked properly during pre-hook');
		$this->assertEquals(ILockingProvider::LOCK_EXCLUSIVE, $lockTypeTargetDuring, 'Target path locked properly during operation');
		$this->assertEquals(ILockingProvider::LOCK_SHARED, $lockTypeTargetPost, 'Target path locked properly during post-hook');

		$this->assertNull($lockTypeSharedRootPre, 'Shared storage root not locked during pre-hook');
		$this->assertNull($lockTypeSharedRootDuring, 'Shared storage root not locked during move');
		$this->assertNull($lockTypeSharedRootPost, 'Shared storage root not locked during post-hook');

		$this->assertNull($this->getFileLockType($view, $sourcePath, false), 'Shared storage root not locked after operation');
		$this->assertNull($this->getFileLockType($view, $sourcePath, true), 'Source path not locked after operation');
		$this->assertNull($this->getFileLockType($view, $targetPath, true), 'Target path not locked after operation');
	}

	/**
	 * Connect hook callbacks for hook type
	 *
	 * @param string $hookType hook type or null for none
	 * @param \OC\Files\View $view view to check the lock on
	 * @param string $path path for which to check the lock
	 * @param int $lockTypePre variable to receive lock type that was active in the pre-hook
	 * @param int $lockTypePost variable to receive lock type that was active in the post-hook
	 * @param bool $onMountPoint true to check the mount point instead of the
	 * mounted storage
	 */
	private function connectMockHooks($hookType, $view, $path, &$lockTypePre, &$lockTypePost, $onMountPoint = false) {
		if ($hookType === null) {
			return;
		}

		$eventHandler = $this->getMockBuilder('\stdclass')
			->setMethods(['preCallback', 'postCallback'])
			->getMock();

		$eventHandler->expects($this->any())
			->method('preCallback')
			->will($this->returnCallback(
				function () use ($view, $path, $onMountPoint, &$lockTypePre) {
					$lockTypePre = $this->getFileLockType($view, $path, $onMountPoint);
				}
			));
		$eventHandler->expects($this->any())
			->method('postCallback')
			->will($this->returnCallback(
				function () use ($view, $path, $onMountPoint, &$lockTypePost) {
					$lockTypePost = $this->getFileLockType($view, $path, $onMountPoint);
				}
			));

		if ($hookType !== null) {
			\OCP\Util::connectHook(
				\OC\Files\Filesystem::CLASSNAME,
				$hookType,
				$eventHandler,
				'preCallback'
			);
			\OCP\Util::connectHook(
				\OC\Files\Filesystem::CLASSNAME,
				'post_' . $hookType,
				$eventHandler,
				'postCallback'
			);
		}
	}

	/**
	 * Returns the file lock type
	 *
	 * @param \OC\Files\View $view view
	 * @param string $path path
	 * @param bool $onMountPoint true to check the mount point instead of the
	 * mounted storage
	 *
	 * @return int lock type or null if file was not locked
	 */
	private function getFileLockType(\OC\Files\View $view, $path, $onMountPoint = false) {
		if ($this->isFileLocked($view, $path, ILockingProvider::LOCK_EXCLUSIVE, $onMountPoint)) {
			return ILockingProvider::LOCK_EXCLUSIVE;
		} else if ($this->isFileLocked($view, $path, ILockingProvider::LOCK_SHARED, $onMountPoint)) {
			return ILockingProvider::LOCK_SHARED;
		}
		return null;
	}


	public function testRemoveMoveableMountPoint() {
		$mountPoint = '/' . $this->user . '/files/mount/';

		// Mock the mount point
		$mount = $this->getMockBuilder('\Test\TestMoveableMountPoint')
			->disableOriginalConstructor()
			->getMock();
		$mount->expects($this->once())
			->method('getMountPoint')
			->willReturn($mountPoint);
		$mount->expects($this->once())
			->method('removeMount')
			->willReturn('foo');
		$mount->expects($this->any())
			->method('getInternalPath')
			->willReturn('');

		// Register mount
		\OC\Files\Filesystem::getMountManager()->addMount($mount);

		// Listen for events
		$eventHandler = $this->getMockBuilder('\stdclass')
			->setMethods(['umount', 'post_umount'])
			->getMock();
		$eventHandler->expects($this->once())
			->method('umount')
			->with([\OC\Files\Filesystem::signal_param_path => '/mount']);
		$eventHandler->expects($this->once())
			->method('post_umount')
			->with([\OC\Files\Filesystem::signal_param_path => '/mount']);
		\OCP\Util::connectHook(
			\OC\Files\Filesystem::CLASSNAME,
			'umount',
			$eventHandler,
			'umount'
		);
		\OCP\Util::connectHook(
			\OC\Files\Filesystem::CLASSNAME,
			'post_umount',
			$eventHandler,
			'post_umount'
		);

		//Delete the mountpoint
		$view = new \OC\Files\View('/' . $this->user . '/files');
		$this->assertEquals('foo', $view->rmdir('mount'));
	}

	public function mimeFilterProvider() {
		return [
			[null, ['test1.txt', 'test2.txt', 'test3.md', 'test4.png']],
			['text/plain', ['test1.txt', 'test2.txt']],
			['text/markdown', ['test3.md']],
			['text', ['test1.txt', 'test2.txt', 'test3.md']],
		];
	}

	/**
	 * @param string $filter
	 * @param string[] $expected
	 * @dataProvider mimeFilterProvider
	 */
	public function testGetDirectoryContentMimeFilter($filter, $expected) {
		$storage1 = new Temporary();
		$root = $this->getUniqueID('/');
		\OC\Files\Filesystem::mount($storage1, array(), $root . '/');
		$view = new \OC\Files\View($root);

		$view->file_put_contents('test1.txt', 'asd');
		$view->file_put_contents('test2.txt', 'asd');
		$view->file_put_contents('test3.md', 'asd');
		$view->file_put_contents('test4.png', '');

		$content = $view->getDirectoryContent('', $filter);

		$files = array_map(function (FileInfo $info) {
			return $info->getName();
		}, $content);
		sort($files);

		$this->assertEquals($expected, $files);
	}

	public function testFilePutContentsClearsChecksum() {
		$storage = new Temporary(array());
		$scanner = $storage->getScanner();
		$storage->file_put_contents('foo.txt', 'bar');
		\OC\Files\Filesystem::mount($storage, array(), '/test/');
		$scanner->scan('');

		$view = new \OC\Files\View('/test/foo.txt');
		$view->putFileInfo('.', ['checksum' => '42']);

		$this->assertEquals('bar', $view->file_get_contents(''));
		$fh = tmpfile();
		fwrite($fh, 'fooo');
		rewind($fh);
		$view->file_put_contents('', $fh);
		$this->assertEquals('fooo', $view->file_get_contents(''));
		$data = $view->getFileInfo('.');
		$this->assertEquals('', $data->getChecksum());
	}

	public function testDeleteGhostFile() {
		$storage = new Temporary(array());
		$scanner = $storage->getScanner();
		$cache = $storage->getCache();
		$storage->file_put_contents('foo.txt', 'bar');
		\OC\Files\Filesystem::mount($storage, array(), '/test/');
		$scanner->scan('');

		$storage->unlink('foo.txt');

		$this->assertTrue($cache->inCache('foo.txt'));

		$view = new \OC\Files\View('/test');
		$rootInfo = $view->getFileInfo('');
		$this->assertEquals(3, $rootInfo->getSize());
		$view->unlink('foo.txt');
		$newInfo = $view->getFileInfo('');

		$this->assertFalse($cache->inCache('foo.txt'));
		$this->assertNotEquals($rootInfo->getEtag(), $newInfo->getEtag());
		$this->assertEquals(0, $newInfo->getSize());
	}

	public function testDeleteGhostFolder() {
		$storage = new Temporary(array());
		$scanner = $storage->getScanner();
		$cache = $storage->getCache();
		$storage->mkdir('foo');
		$storage->file_put_contents('foo/foo.txt', 'bar');
		\OC\Files\Filesystem::mount($storage, array(), '/test/');
		$scanner->scan('');

		$storage->rmdir('foo');

		$this->assertTrue($cache->inCache('foo'));
		$this->assertTrue($cache->inCache('foo/foo.txt'));

		$view = new \OC\Files\View('/test');
		$rootInfo = $view->getFileInfo('');
		$this->assertEquals(3, $rootInfo->getSize());
		$view->rmdir('foo');
		$newInfo = $view->getFileInfo('');

		$this->assertFalse($cache->inCache('foo'));
		$this->assertFalse($cache->inCache('foo/foo.txt'));
		$this->assertNotEquals($rootInfo->getEtag(), $newInfo->getEtag());
		$this->assertEquals(0, $newInfo->getSize());
	}
}
