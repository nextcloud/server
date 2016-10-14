<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

use OC\Files\FileInfo;

class NodeTest extends \Test\TestCase {
	/** @var \OC\User\User */
	private $user;

	/** @var \OC\Files\Mount\Manager */
	private $manager;

	/** @var \OC\Files\View|\PHPUnit_Framework_MockObject_MockObject */
	private $view;

	/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject */
	private $root;
	/** @var \OCP\Files\Config\IUserMountCache|\PHPUnit_Framework_MockObject_MockObject */
	private $userMountCache;

	protected function setUp() {
		parent::setUp();

		$config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$urlGenerator = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();

		$this->user = new \OC\User\User('', new \Test\Util\User\Dummy, null, $config, $urlGenerator);

		$this->manager = $this->getMockBuilder('\OC\Files\Mount\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->view = $this->getMockBuilder('\OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$this->userMountCache = $this->getMockBuilder('\OCP\Files\Config\IUserMountCache')
			->disableOriginalConstructor()
			->getMock();
		$this->root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache])
			->getMock();
	}

	protected function getMockStorage() {
		$storage = $this->getMockBuilder('\OCP\Files\Storage')
			->disableOriginalConstructor()
			->getMock();
		$storage->expects($this->any())
			->method('getId')
			->will($this->returnValue('home::someuser'));
		return $storage;
	}

	protected function getFileInfo($data) {
		return new FileInfo('', $this->getMockStorage(), '', $data, null);
	}

	public function testStat() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50,
			'permissions' => 0
		);

		$this->view->expects($this->once())
			->method('stat')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals($stat, $node->stat());
	}

	public function testGetId() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals(1, $node->getId());
	}

	public function testGetSize() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));


		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals(100, $node->getSize());
	}

	public function testGetEtag() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals('qwerty', $node->getEtag());
	}

	public function testGetMTime() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$stat = $this->getFileInfo(array(
			'fileid' => 1,
			'size' => 100,
			'etag' => 'qwerty',
			'mtime' => 50
		));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($stat));

		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals(50, $node->getMTime());
	}

	public function testGetStorage() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		/**
		 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();

		$this->view->expects($this->once())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array($storage, 'foo')));


		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals($storage, $node->getStorage());
	}

	public function testGetPath() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals('/bar/foo', $node->getPath());
	}

	public function testGetInternalPath() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		/**
		 * @var \OC\Files\Storage\Storage | \PHPUnit_Framework_MockObject_MockObject $storage
		 */
		$storage = $this->getMockBuilder('\OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();

		$this->view->expects($this->once())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array($storage, 'foo')));


		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals('foo', $node->getInternalPath());
	}

	public function testGetName() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$node = new \OC\Files\Node\File($this->root, $this->view, '/bar/foo');
		$this->assertEquals('foo', $node->getName());
	}

	public function testTouchSetMTime() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->view->expects($this->once())
			->method('touch')
			->with('/bar/foo', 100)
			->will($this->returnValue(true));

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$node = new \OC\Files\Node\Node($this->root, $this->view, '/bar/foo');
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

		$root = new \OC\Files\Node\Root($this->manager, $this->view, $this->user, $this->userMountCache);
		$root->listen('\OC\Files', 'preTouch', $preListener);
		$root->listen('\OC\Files', 'postTouch', $postListener);

		$this->view->expects($this->once())
			->method('touch')
			->with('/bar/foo', 100)
			->will($this->returnValue(true));

		$this->view->expects($this->any())
			->method('resolvePath')
			->with('/bar/foo')
			->will($this->returnValue(array(null, 'foo')));

		$this->view->expects($this->any())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_ALL))));

		$node = new \OC\Files\Node\Node($root, $this->view, '/bar/foo');
		$node->touch(100);
		$this->assertEquals(2, $hooksRun);
	}

	/**
	 * @expectedException \OCP\Files\NotPermittedException
	 */
	public function testTouchNotPermitted() {
		$this->root->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->view->expects($this->any())
			->method('getFileInfo')
			->with('/bar/foo')
			->will($this->returnValue($this->getFileInfo(array('permissions' => \OCP\Constants::PERMISSION_READ))));

		$node = new \OC\Files\Node\Node($this->root, $this->view, '/bar/foo');
		$node->touch(100);
	}

	/**
	 * @expectedException \OCP\Files\InvalidPathException
	 */
	public function testInvalidPath() {
		$node = new \OC\Files\Node\Node($this->root, $this->view, '/../foo');
		$node->getFileInfo();
	}
}
