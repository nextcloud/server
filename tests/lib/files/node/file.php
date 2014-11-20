<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OC\Files\View;

class File extends \Test\TestCase {
	private $user;

	protected function setUp() {
		parent::setUp();
		$this->user = new \OC\User\User('', new \OC_User_Dummy);
	}

	public function testDelete() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');

		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');

		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->exactly(2))
			->method('emit')
			->will($this->returnValue(true));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL)));

		$view->expects($this->once())
			->method('unlink')
			->with('/bar/foo')
			->will($this->returnValue(true));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->delete();
	}

	public function testDeleteHooks() {
		$test = $this;
		$hooksRun = 0;
		/**
		 * @param \OC\Files\Node\File $node
		 */
		$preListener = function ($node) use (&$test, &$hooksRun) {
			$test->assertInstanceOf('\OC\Files\Node\File', $node);
			$test->assertEquals('foo', $node->getInternalPath());
			$test->assertEquals('/bar/foo', $node->getPath());
			$test->assertEquals(1, $node->getId());
			$hooksRun++;
		};

		/**
		 * @param \OC\Files\Node\File $node
		 */
		$postListener = function ($node) use (&$test, &$hooksRun) {
			$test->assertInstanceOf('\OC\Files\Node\NonExistingFile', $node);
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
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL, 'fileid' => 1)));

		$view->expects($this->once())
			->method('unlink')
			->with('/bar/foo')
			->will($this->returnValue(true));

		$view->expects($this->any())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array(null, 'foo')));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
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

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->delete();
	}

	public function testGetContent() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$hook = function ($file) {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$view->expects($this->once())
			->method('file_get_contents')
			->with('/bar/foo')
			->will($this->returnValue('bar'));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals('bar', $node->getContent());
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testGetContentNotPermitted() {
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
			->will($this->returnValue(array('permissions' => 0)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->getContent();
	}

	public function testPutContent() {
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
			->method('file_put_contents')
			->with('/bar/foo', 'bar')
			->will($this->returnValue(true));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->putContent('bar');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testPutContentNotPermitted() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->putContent('bar');
	}

	public function testGetMimeType() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->once())
			->method('getMimeType')
			->with('/bar/foo')
			->will($this->returnValue('text/plain'));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals('text/plain', $node->getMimeType());
	}

	public function testFOpenRead() {
		$stream = fopen('php://memory', 'w+');
		fwrite($stream, 'bar');
		rewind($stream);

		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$hook = function ($file) {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$view->expects($this->once())
			->method('fopen')
			->with('/bar/foo', 'r')
			->will($this->returnValue($stream));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$fh = $node->fopen('r');
		$this->assertEquals($stream, $fh);
		$this->assertEquals('bar', fread($fh, 3));
	}

	public function testFOpenWrite() {
		$stream = fopen('php://memory', 'w+');

		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, new $view, $this->user);

		$hooksCalled = 0;
		$hook = function ($file) use (&$hooksCalled) {
			$hooksCalled++;
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$view->expects($this->once())
			->method('fopen')
			->with('/bar/foo', 'w')
			->will($this->returnValue($stream));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$fh = $node->fopen('w');
		$this->assertEquals($stream, $fh);
		fwrite($fh, 'bar');
		rewind($fh);
		$this->assertEquals('bar', fread($stream, 3));
		$this->assertEquals(2, $hooksCalled);
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testFOpenReadNotPermitted() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$hook = function ($file) {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => 0)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->fopen('r');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testFOpenReadWriteNoReadPermissions() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, $view, $this->user);

		$hook = function () {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_UPDATE)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->fopen('w');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testFOpenReadWriteNoWritePermissions() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = new \OC\Files\Node\Root($manager, new $view, $this->user);

		$hook = function () {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$node->fopen('w');
	}

	public function testCopySameStorage() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->any())
			->method('copy')
			->with('/bar/foo', '/bar/asd');

		$view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL, 'fileid' => 3)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $view, '/bar');
		$newNode = new \OC\Files\Node\File($root, $view, '/bar/asd');

		$root->expects($this->exactly(2))
			->method('get')
			->will($this->returnValueMap(array(
				array('/bar/asd', $newNode),
				array('/bar', $parentNode)
			)));

		$target = $node->copy('/bar/asd');
		$this->assertInstanceOf('\OC\Files\Node\File', $target);
		$this->assertEquals(3, $target->getId());
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testCopyNotPermitted() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		/**
		 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMock('\OC\Files\Storage\Storage');

		$root->expects($this->never())
			->method('getMount');

		$storage->expects($this->never())
			->method('copy');

		$view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ, 'fileid' => 3)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $view, '/bar');

		$root->expects($this->once())
			->method('get')
			->will($this->returnValueMap(array(
				array('/bar', $parentNode)
			)));

		$node->copy('/bar/asd');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	public function testCopyNoParent() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->never())
			->method('copy');

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');

		$root->expects($this->once())
			->method('get')
			->with('/bar/asd')
			->will($this->throwException(new NotFoundException()));

		$node->copy('/bar/asd/foo');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testCopyParentIsFile() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->never())
			->method('copy');

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$parentNode = new \OC\Files\Node\File($root, $view, '/bar');

		$root->expects($this->once())
			->method('get')
			->will($this->returnValueMap(array(
				array('/bar', $parentNode)
			)));

		$node->copy('/bar/asd');
	}

	public function testMoveSameStorage() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->any())
			->method('rename')
			->with('/bar/foo', '/bar/asd');

		$view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_ALL, 'fileid' => 1)));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $view, '/bar');

		$root->expects($this->any())
			->method('get')
			->will($this->returnValueMap(array(array('/bar', $parentNode), array('/bar/asd', $node))));

		$target = $node->move('/bar/asd');
		$this->assertInstanceOf('\OC\Files\Node\File', $target);
		$this->assertEquals(1, $target->getId());
		$this->assertEquals('/bar/asd', $node->getPath());
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testMoveNotPermitted() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue(array('permissions' => \OCP\PERMISSION_READ)));

		$view->expects($this->never())
			->method('rename');

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $view, '/bar');

		$root->expects($this->once())
			->method('get')
			->with('/bar')
			->will($this->returnValue($parentNode));

		$node->move('/bar/asd');
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	public function testMoveNoParent() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		/**
		 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMock('\OC\Files\Storage\Storage');

		$storage->expects($this->never())
			->method('rename');

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $view, '/bar');

		$root->expects($this->once())
			->method('get')
			->with('/bar')
			->will($this->throwException(new NotFoundException()));

		$node->move('/bar/asd');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testMoveParentIsFile() {
		/**
		 * @var \OC\Files\Mount\Manager $manager
		 */
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$view->expects($this->never())
			->method('rename');

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$parentNode = new \OC\Files\Node\File($root, $view, '/bar');

		$root->expects($this->once())
			->method('get')
			->with('/bar')
			->will($this->returnValue($parentNode));

		$node->move('/bar/asd');
	}
}
