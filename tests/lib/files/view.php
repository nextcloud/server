<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file. */

namespace Test\Files;

use OC\Files\Cache\Watcher;

class TemporaryNoTouch extends \OC\Files\Storage\Temporary {
	public function touch($path, $mtime = null) {
		return false;
	}
}

class View extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Files\Storage\Storage[] $storages
	 */
	private $storages = array();
	private $user;

	public function setUp() {
		\OC_User::clearBackends();
		\OC_User::useBackend(new \OC_User_Dummy());

		//login
		\OC_User::createUser('test', 'test');
		$this->user = \OC_User::getUser();
		\OC_User::setUserId('test');

		\OC\Files\Filesystem::clearMounts();
	}

	public function tearDown() {
		\OC_User::setUserId($this->user);
		foreach ($this->storages as $storage) {
			$cache = $storage->getCache();
			$ids = $cache->getAll();
			$cache->clear();
		}
	}

	/**
	 * @medium
	 */
	public function testCacheAPI() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$storage3 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');
		\OC\Files\Filesystem::mount($storage3, array(), '/folder/anotherstorage');
		$textSize = strlen("dummy file data\n");
		$imageSize = filesize(\OC::$SERVERROOT . '/core/img/logo.png');
		$storageSize = $textSize * 2 + $imageSize;

		$rootView = new \OC\Files\View('');

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

		$folderView = new \OC\Files\View('/folder');
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
	function testGetPath() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
		$storage3 = $this->getTestStorage();
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');
		\OC\Files\Filesystem::mount($storage3, array(), '/folder/anotherstorage');

		$rootView = new \OC\Files\View('');

		$cachedData = $rootView->getFileInfo('/foo.txt');
		$id1 = $cachedData['fileid'];
		$this->assertEquals('/foo.txt', $rootView->getPath($id1));

		$cachedData = $rootView->getFileInfo('/substorage/foo.txt');
		$id2 = $cachedData['fileid'];
		$this->assertEquals('/substorage/foo.txt', $rootView->getPath($id2));

		$folderView = new \OC\Files\View('/substorage');
		$this->assertEquals('/foo.txt', $folderView->getPath($id2));
		$this->assertNull($folderView->getPath($id1));
	}

	/**
	 * @medium
	 */
	function testMountPointOverwrite() {
		$storage1 = $this->getTestStorage(false);
		$storage2 = $this->getTestStorage();
		$storage1->mkdir('substorage');
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		\OC\Files\Filesystem::mount($storage2, array(), '/substorage');

		$rootView = new \OC\Files\View('');
		$folderContent = $rootView->getDirectoryContent('/');
		$this->assertEquals(4, count($folderContent));
	}

	function testCacheIncompleteFolder() {
		$storage1 = $this->getTestStorage(false);
		\OC\Files\Filesystem::mount($storage1, array(), '/');
		$rootView = new \OC\Files\View('');

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
	function testSearch() {
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
	function testWatcher() {
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
	function testCopyBetweenStorages() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
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
	function testMoveBetweenStorages() {
		$storage1 = $this->getTestStorage();
		$storage2 = $this->getTestStorage();
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
	function testUnlink() {
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
	function testUnlinkRootMustFail() {
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
	function testTouch() {
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
		$this->assertGreaterThanOrEqual($cachedData['mtime'], $oldCachedData['mtime']);
		$this->assertEquals($cachedData['storage_mtime'], $cachedData['mtime']);
	}

	/**
	 * @medium
	 */
	function testViewHooks() {
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
	function testViewHooksIfRootStartsTheSame() {
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

	public function testLongPath() {

		$storage = new \OC\Files\Storage\Temporary(array());
		\OC\Files\Filesystem::mount($storage, array(), '/');

		$rootView = new \OC\Files\View('');

		$longPath = '';
		// 4000 is the maximum path length in file_cache.path
		$folderName = 'abcdefghijklmnopqrstuvwxyz012345678901234567890123456789';
		$depth = (4000/57);
		foreach (range(0, $depth-1) as $i) {
			$longPath .= '/'.$folderName;
			$result = $rootView->mkdir($longPath);
			$this->assertTrue($result, "mkdir failed on $i - path length: " . strlen($longPath));

			$result = $rootView->file_put_contents($longPath . '/test.txt', 'lorem');
			$this->assertEquals(5, $result, "file_put_contents failed on $i");

			$this->assertTrue($rootView->file_exists($longPath));
			$this->assertTrue($rootView->file_exists($longPath . '/test.txt'));
		}

		$cache = $storage->getCache();
		$scanner = $storage->getScanner();
		$scanner->scan('');

		$longPath = $folderName;
		foreach (range(0, $depth-1) as $i) {
			$cachedFolder = $cache->get($longPath);
			$this->assertTrue(is_array($cachedFolder), "No cache entry for folder at $i");
			$this->assertEquals($folderName, $cachedFolder['name'], "Wrong cache entry for folder at $i");

			$cachedFile = $cache->get($longPath . '/test.txt');
			$this->assertTrue(is_array($cachedFile), "No cache entry for file at $i");
			$this->assertEquals('test.txt', $cachedFile['name'], "Wrong cache entry for file at $i");

			$longPath .= '/' . $folderName;
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

	/**
	 * @dataProvider absolutePathProvider
	 */
	public function testGetAbsolutePath($expectedPath, $relativePath) {
		$view = new \OC\Files\View('/files');
		$this->assertEquals($expectedPath, $view->getAbsolutePath($relativePath));
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
	 * @dataProvider tooLongPathDataProvider
	 * @expectedException \OCP\Files\InvalidPathException
	 */
	public function testTooLongPath($operation, $param0 = NULL) {

		$longPath = '';
		// 4000 is the maximum path length in file_cache.path
		$folderName = 'abcdefghijklmnopqrstuvwxyz012345678901234567890123456789';
		$depth = (4000/57);
		foreach (range(0, $depth+1) as $i) {
			$longPath .= '/'.$folderName;
		}

		$storage = new \OC\Files\Storage\Temporary(array());
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
}
