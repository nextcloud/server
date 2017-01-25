<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Node;

/**
 * Class FileTest
 *
 * @group DB
 *
 * @package Test\Files\Node
 */
class FileTest extends NodeTest {
	protected function createTestNode($root, $view, $path) {
		return new \OC\Files\Node\File($root, $view, $path);
	}

	protected function getNodeClass() {
		return '\OC\Files\Node\File';
	}

	protected function getNonExistingNodeClass() {
		return '\OC\Files\Node\NonExistingFile';
	}

	protected function getViewDeleteMethod() {
		return 'unlink';
	}

	public function testGetContent() {
		/** @var \OC\Files\Node\Root|\PHPUnit_Framework_MockObject_MockObject $root */
		$root = $this->getMockBuilder('\OC\Files\Node\Root')
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager])
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
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager])
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
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager])
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
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager])
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
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager])
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

		$root = new \OC\Files\Node\Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);

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

		$root = new \OC\Files\Node\Root(
			$this->manager,
			new $this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);
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
		$root = new \OC\Files\Node\Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);
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
		$root = new \OC\Files\Node\Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);
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
		$root = new \OC\Files\Node\Root(
			$this->manager,
			new $this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager
		);
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


}
