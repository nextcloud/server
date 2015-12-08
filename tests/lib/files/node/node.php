<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\FileInfo;

class Node extends \Test\TestCase {
	private $user;

	protected function setUp() {
		parent::setUp();
		$this->user = new \OC\User\User('', new \Test\Util\User\Dummy);
	}

	protected function getMockStorage() {
		$storage = $this->getMock('\OCP\Files\Storage');
		$storage->expects($this->any())
			->method('getId')
			->will($this->returnValue('home::someuser'));
		return $storage;
	}

	protected function getFileInfo($data) {
		return new FileInfo('', $this->getMockStorage(), '', $data, null);
	}

	public function testStat() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50,
			'permissions' => 0
		);

		$view->expects($this->once())
			->method('stat')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals($stat, $node->stat());
	}

	public function testGetId() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals(1, $node->getId());
	}

	public function testGetSize() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));


		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals(100, $node->getSize());
	}

	public function testGetEtag() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals('qwerty', $node->getEtag());
	}

	public function testGetMTime() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals(50, $node->getMTime());
	}

	public function testGetStorage() {
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

		$view->expects($this->once())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array($storage, 'foo')));


		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals($storage, $node->getStorage());
	}

	public function testGetPath() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals('/bar/foo', $node->getPath());
	}

	public function testGetInternalPath() {
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

		$view->expects($this->once())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array($storage, 'foo')));


		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals('foo', $node->getInternalPath());
	}

	public function testGetName() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$node = new \OC\Files\Node\File($root, $view, '/bar/foo');
		$this->assertEquals('foo', $node->getName());
	}

	public function testTouchSetMTime() {
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
			->method('touch')
			->with('/bar/foo', 100)
			->will($this->returnValue(true));

		$view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$node = new \OC\Files\Node\Node($root, $view, '/bar/foo');
		$node->touch(100);
		$this->assertEquals(100, $node->getMTime());
	}

	public function testTouchHooks() {
		$test = $this;
		$hooksRun = 0;
		/**
		 * @param \OC\Files\Node\File $node
		 */
		$preListener = function ($node) use (&$test, &$hooksRun) {
			$test->assertEquals('foo', $node->getInternalPath());
			$test->assertEquals('/bar/foo', $node->getPath());
			$hooksRun++;
		};

		/**
		 * @param \OC\Files\Node\File $node
		 */
		$postListener = function ($node) use (&$test, &$hooksRun) {
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
		$root->listen('\OC\Files', 'preTouch', $preListener);
		$root->listen('\OC\Files', 'postTouch', $postListener);

		$view->expects($this->once())
			->method('touch')
			->with('/bar/foo', 100)
			->will($this->returnValue(true));

		$view->expects($this->any())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array(null, 'foo')));

		$view->expects($this->any())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$node = new \OC\Files\Node\Node($root, $view, '/bar/foo');
		$node->touch(100);
		$this->assertEquals(2, $hooksRun);
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testTouchNotPermitted() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));
		$root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$view->expects($this->any())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ))));

		$node = new \OC\Files\Node\Node($root, $view, '/bar/foo');
		$node->touch(100);
	}

	/**
	 * @expectedException \OCP\Files\InvalidPathException
	 */
	public function testInvalidPath() {
		$manager = $this->getMock('\OC\Files\Mount\Manager');
		/**
		 * @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject $view
		 */
		$view = $this->getMock('\OC\Files\View');
		$root = $this->getMock('\OC\Files\Node\Root', array(), array($manager, $view, $this->user));

		$node = new \OC\Files\Node\Node($root, $view, '/../foo');
		$node->getFileInfo();
	}
}
