<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\FileInfo;
use OCP\Files\NotFoundException;

class FileTest extends \Test\TestCase {
	/** @var \OC\User\User */
	private $user;

	/** @var \OC\Files\Mount\Manager */
	private $manager;

	/** @var \OC\Files\View|\PHPUnit_Framework_MockObject_MockObject */
	private $view;

	/** @var \OCP\Files\Config\IUserMountCache|\PHPUnit_Framework_MockObject_MockObject */
	private $userMountCache;

	protected function setUp() {
		parent::setUp();
		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->user = new \OC\User\User('', new \Test\Util\User\Dummy, null, $config);

		$this->manager = $this->getMockBuilder('\OC\Files\Mount\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$this->userMountCache = $this->getMockBuilder('\OCP\Files\Config\IUserMountCache')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function getMockStorage() {
		$storage = $this->getMockBuilder('\OCP\Files\Storage')
			->getMock();
		$storage->expects($this->any())
			->method('getId')
			->will($this->returnValue('home::someuser'));
		return $storage;
	}

	protected function getFileInfo($data) {
		return new FileInfo('', $this->getMockStorage(), '', $data, null);
	}

	public function testDelete() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$root->expects($this->exactly(2))
			->method('emit')
			->will($this->returnValue(true));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$this->view->expects($this->once())
			->method('unlink')
			->with('/bar/foo')
			->will($this->returnValue(true));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
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
			$test->assertEquals(1, $node->getId());
			$test->assertEquals('text/plain', $node->getMimeType());
			$hooksRun++;
		};

		$root = new \OC\Files\Node\Root($this->manager, $this->view, $this->user, $this->userMountCache);
		$root->listen('\OC\Files', 'preDelete', $preListener);
		$root->listen('\OC\Files', 'postDelete', $postListener);

		$this->view->expects($this->any())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL, 'fileid' => 1, 'mimetype' => 'text/plain'))));

		$this->view->expects($this->once())
			->method('unlink')
			->with('/bar/foo')
			->will($this->returnValue(true));

		$this->view->expects($this->any())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array(null, 'foo')));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->delete();
		$this->assertEquals(2, $hooksRun);
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testDeleteNotPermitted() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->delete();
	}

	public function testGetContent() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$hook = function ($file) {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$this->view->expects($this->once())
			->method('file_get_contents')
			->with('/bar/foo')
			->will($this->returnValue('bar'));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$this->assertEquals('bar', $node->getContent());
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testGetContentNotPermitted() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => 0))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->getContent();
	}

	public function testPutContent() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$this->view->expects($this->once())
			->method('file_put_contents')
			->with('/bar/foo', 'bar')
			->will($this->returnValue(true));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->putContent('bar');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testPutContentNotPermitted() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->putContent('bar');
	}

	public function testGetMimeType() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('mimetype' => 'text/plain'))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$this->assertEquals('text/plain', $node->getMimeType());
	}

	public function testFOpenRead() {
		$stream = fopen('php://memory', 'w+');
		fwrite($stream, 'bar');
		rewind($stream);

		$root = new \OC\Files\Node\Root($this->manager, $this->view, $this->user, $this->userMountCache);

		$hook = function ($file) {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$this->view->expects($this->once())
			->method('fopen')
			->with('/bar/foo', 'r')
			->will($this->returnValue($stream));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$fh = $node->fopen('r');
		$this->assertEquals($stream, $fh);
		$this->assertEquals('bar', fread($fh, 3));
	}

	public function testFOpenWrite() {
		$stream = fopen('php://memory', 'w+');

		$root = new \OC\Files\Node\Root($this->manager, new $this->view, $this->user, $this->userMountCache);

		$hooksCalled = 0;
		$hook = function ($file) use (&$hooksCalled) {
			$hooksCalled++;
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$this->view->expects($this->once())
			->method('fopen')
			->with('/bar/foo', 'w')
			->will($this->returnValue($stream));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
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
		$root = new \OC\Files\Node\Root($this->manager, $this->view, $this->user, $this->userMountCache);

		$hook = function ($file) {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => 0))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->fopen('r');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testFOpenReadWriteNoReadPermissions() {
		$root = new \OC\Files\Node\Root($this->manager, $this->view, $this->user, $this->userMountCache);

		$hook = function () {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_UPDATE))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->fopen('w');
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testFOpenReadWriteNoWritePermissions() {
		$root = new \OC\Files\Node\Root($this->manager, new $this->view, $this->user, $this->userMountCache);

		$hook = function () {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$node->fopen('w');
	}

	public function testCopySameStorage() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->any())
			->method('copy')
			->with('/bar/foo', '/bar/asd');

		$this->view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL, 'fileid' => 3))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $this->view, '/bar');
		$newNode = new \OC\Files\Node\File($root, $this->view, '/bar/asd');

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
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		/**
		 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();

		$root->expects($this->never())
			->method('getMount');

		$storage->expects($this->never())
			->method('copy');

		$this->view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ, 'fileid' => 3))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $this->view, '/bar');

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
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->never())
			->method('copy');

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');

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
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->never())
			->method('copy');

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$parentNode = new \OC\Files\Node\File($root, $this->view, '/bar');

		$root->expects($this->once())
			->method('get')
			->will($this->returnValueMap(array(
				array('/bar', $parentNode)
			)));

		$node->copy('/bar/asd');
	}

	public function testMoveSameStorage() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->any())
			->method('rename')
			->with('/bar/foo', '/bar/asd');

		$this->view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL, 'fileid' => 1))));

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $this->view, '/bar');

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
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->any())
			->method('getFileInfo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ))));

		$this->view->expects($this->never())
			->method('rename');

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $this->view, '/bar');

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
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		/**
		 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();

		$storage->expects($this->never())
			->method('rename');

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$parentNode = new \OC\Files\Node\Folder($root, $this->view, '/bar');

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
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();

		$this->view->expects($this->never())
			->method('rename');

		$node = new \OC\Files\Node\File($root, $this->view, '/bar/foo');
		$parentNode = new \OC\Files\Node\File($root, $this->view, '/bar');

		$root->expects($this->once())
			->method('get')
			->with('/bar')
			->will($this->returnValue($parentNode));

		$node->move('/bar/asd');
	}
}
