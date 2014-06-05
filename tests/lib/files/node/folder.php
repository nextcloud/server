<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\Cache\Cache;
use OC\Files\Node\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OC\Files\View;

class Folder extends \PHPUnit_Framework_TestCase {
	private $user;

	public function setUp() {
		$this->user = new \OC\User\User('', new \OC_User_Dummy);
	}

	public function testDelete() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		$root->expects($this->exactly(2))
			->method('emit')
			->will($this->returnValue(true));

		$view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL)));

		$view->expects($this->once())
			->method('rmdir')
			->with('/bar/foo')
			->will($this->returnValue(true));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$node->delete();
	}

	public function testDeleteHooks() {
		$test = $this;
		$hooksRun = 0;
		/**
		 * @param \OC\Files\Node\File $node
		 */
		$preListener = function ($node) use (&$test, &$hooksRun) {
			$test->assertInstanceOf('\OC\Files\Node\Folder', $node);
			$test->assertEquals('foo', $node->getInternalPath());
			$test->assertEquals('/bar/foo', $node->getPath());
			$hooksRun++;
		};

		/**
		 * @param \OC\Files\Node\File $node
		 */
		$postListener = function ($node) use (&$test, &$hooksRun) {
			$test->assertInstanceOf('\OC\Files\Node\NonExistingFolder', $node);
			$test->assertEquals('foo', $node->getInternalPath());
			$test->assertEquals('/bar/foo', $node->getPath());
			$hooksRun++;
		};

		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);
		$root->listen('\OC\Files', 'preDelete', $preListener);
		$root->listen('\OC\Files', 'postDelete', $postListener);

		$view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL, 'fileid' => 1)));

		$view->expects($this->once())
			->method('rmdir')
			->with('/bar/foo')
			->will($this->returnValue(true));

		$view->expects($this->any())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array(null, 'foo')));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$node->delete();
		$this->assertEquals(2, $hooksRun);
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testDeleteNotPermitted() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ)));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$node->delete();
	}

	public function testGetDirectoryContent() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		/**
		 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMock('\OC\Files\Storage\Storage');

		$cache = $this->getMock('\OC\Files\Cache\Cache', array(), array(''));
		$cache->expects($this->any())
			->method('getStatus')
			->with('foo')
			->will($this->returnValue(Cache::COMPLETE));

		$cache->expects($this->once())
			->method('getFolderContents')
			->with('foo')
			->will($this->returnValue(array(
				array('fileid' => 2, 'path' => '/bar/foo/asd', 'name' => 'asd', 'size' => 100, 'mtime' => 50, 'mimetype' => 'text/plain'),
				array('fileid' => 3, 'path' => '/bar/foo/qwerty', 'name' => 'qwerty', 'size' => 200, 'mtime' => 55, 'mimetype' => 'httpd/unix-directory')
			)));

		$root->expects($this->once())
			->method('getMountsIn')
			->with('/bar/foo')
			->will($this->returnValue(array()));

		$storage->expects($this->any())
			->method('getCache')
			->will($this->returnValue($cache));

		$view->expects($this->any())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array($storage, 'foo')));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$children = $node->getDirectoryListing();
		$this->assertEquals(2, count($children));
		$this->assertInstanceOf('\OC\Files\Node\File', $children[0]);
		$this->assertInstanceOf('\OC\Files\Node\Folder', $children[1]);
		$this->assertEquals('asd', $children[0]->getName());
		$this->assertEquals('qwerty', $children[1]->getName());
	}

	public function testGet() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$root->expects($this->once())
			->method('get')
			->with('/bar/foo/asd');

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$node->get('asd');
	}

	public function testNodeExists() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$child = new \OC\Files\Node\Folder($root, $view, '/bar/foo/asd');

		$root->expects($this->once())
			->method('get')
			->with('/bar/foo/asd')
			->will($this->returnValue($child));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$this->assertTrue($node->nodeExists('asd'));
	}

	public function testNodeExistsNotExists() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$root->expects($this->once())
			->method('get')
			->with('/bar/foo/asd')
			->will($this->throwException(new NotFoundException()));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$this->assertFalse($node->nodeExists('asd'));
	}

	public function testNewFolder() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL)));

		$view->expects($this->once())
			->method('mkdir')
			->with('/bar/foo/asd')
			->will($this->returnValue(true));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$child = new \OC\Files\Node\Folder($root, $view, '/bar/foo/asd');
		$result = $node->newFolder('asd');
		$this->assertEquals($child, $result);
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testNewFolderNotPermitted() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ)));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$node->newFolder('asd');
	}

	public function testNewFile() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL)));

		$view->expects($this->once())
			->method('touch')
			->with('/bar/foo/asd')
			->will($this->returnValue(true));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$child = new \OC\Files\Node\File($root, $view, '/bar/foo/asd');
		$result = $node->newFile('asd');
		$this->assertEquals($child, $result);
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testNewFileNotPermitted() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ)));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$node->newFile('asd');
	}

	public function testGetFreeSpace() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->once())
			->method('free_space')
			->with('/bar/foo')
			->will($this->returnValue(100));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$this->assertEquals(100, $node->getFreeSpace());
	}

	public function testSearch() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		$storage = $this->getMock('\OC\Files\Storage\Storage');
		$cache = $this->getMock('\OC\Files\Cache\Cache', array(), array(''));

		$storage->expects($this->once())
			->method('getCache')
			->will($this->returnValue($cache));

		$cache->expects($this->once())
			->method('search')
			->with('%qw%')
			->will($this->returnValue(array(
				array('fileid' => 3, 'path' => 'foo/qwerty', 'name' => 'qwerty', 'size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain')
			)));

		$root->expects($this->once())
			->method('getMountsIn')
			->with('/bar/foo')
			->will($this->returnValue(array()));

		$view->expects($this->once())
			->method('resolvePath')
			->will($this->returnValue(array($storage, 'foo')));

		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$result = $node->search('qw');
		$this->assertEquals(1, count($result));
		$this->assertEquals('/bar/foo/qwerty', $result[0]->getPath());
	}

	public function testSearchSubStorages() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		$storage = $this->getMock('\OC\Files\Storage\Storage');
		$cache = $this->getMock('\OC\Files\Cache\Cache', array(), array(''));
		$subCache = $this->getMock('\OC\Files\Cache\Cache', array(), array(''));
		$subStorage = $this->getMock('\OC\Files\Storage\Storage');
		$subMount = $this->getMock('\OC\Files\Mount\Mount', array(), array(null, ''));

		$subMount->expects($this->once())
			->method('getStorage')
			->will($this->returnValue($subStorage));

		$subMount->expects($this->once())
			->method('getMountPoint')
			->will($this->returnValue('/bar/foo/bar/'));

		$storage->expects($this->once())
			->method('getCache')
			->will($this->returnValue($cache));

		$subStorage->expects($this->once())
			->method('getCache')
			->will($this->returnValue($subCache));

		$cache->expects($this->once())
			->method('search')
			->with('%qw%')
			->will($this->returnValue(array(
				array('fileid' => 3, 'path' => 'foo/qwerty', 'name' => 'qwerty', 'size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain')
			)));

		$subCache->expects($this->once())
			->method('search')
			->with('%qw%')
			->will($this->returnValue(array(
				array('fileid' => 4, 'path' => 'asd/qweasd', 'name' => 'qweasd', 'size' => 200, 'mtime' => 55, 'mimetype' => 'text/plain')
			)));

		$root->expects($this->once())
			->method('getMountsIn')
			->with('/bar/foo')
			->will($this->returnValue(array($subMount)));

		$view->expects($this->once())
			->method('resolvePath')
			->will($this->returnValue(array($storage, 'foo')));


		$node = new \OC\Files\Node\Folder($root, $view, '/bar/foo');
		$result = $node->search('qw');
		$this->assertEquals(2, count($result));
	}

	public function testIsSubNode() {
		$file = new Node(null, null, '/foo/bar');
		$folder = new \OC\Files\Node\Folder(null, null, '/foo');
		$this->assertTrue($folder->isSubNode($file));
		$this->assertFalse($folder->isSubNode($folder));

		$file = new Node(null, null, '/foobar');
		$this->assertFalse($folder->isSubNode($file));
	}
}
