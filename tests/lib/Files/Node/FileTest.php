<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Node;

use OC\Files\Node\File;
use OC\Files\Node\Root;
use OCP\Constants;
use OCP\Files\NotPermittedException;

/**
 * Class FileTest
 *
 * @group DB
 *
 * @package Test\Files\Node
 */
class FileTest extends NodeTestCase {
	protected function createTestNode($root, $view, $path, array $data = [], $internalPath = '', $storage = null) {
		if ($data || $internalPath || $storage) {
			return new File($root, $view, $path, $this->getFileInfo($data, $internalPath, $storage));
		} else {
			return new File($root, $view, $path);
		}
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

	public function testGetContent(): void {
		/** @var \OC\Files\Node\Root|\PHPUnit\Framework\MockObject\MockObject $root */
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();

		$hook = function ($file): void {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$this->view->expects($this->once())
			->method('file_get_contents')
			->with('/bar/foo')
			->willReturn('bar');

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_READ]));

		$node = new File($root, $this->view, '/bar/foo');
		$this->assertEquals('bar', $node->getContent());
	}


	public function testGetContentNotPermitted(): void {
		$this->expectException(NotPermittedException::class);

		/** @var \OC\Files\Node\Root|\PHPUnit\Framework\MockObject\MockObject $root */
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();

		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => 0]));

		$node = new File($root, $this->view, '/bar/foo');
		$node->getContent();
	}

	public function testPutContent(): void {
		/** @var \OC\Files\Node\Root|\PHPUnit\Framework\MockObject\MockObject $root */
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();

		$root->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_ALL]));

		$this->view->expects($this->once())
			->method('file_put_contents')
			->with('/bar/foo', 'bar')
			->willReturn(true);

		$node = new File($root, $this->view, '/bar/foo');
		$node->putContent('bar');
	}


	public function testPutContentNotPermitted(): void {
		$this->expectException(NotPermittedException::class);

		/** @var \OC\Files\Node\Root|\PHPUnit\Framework\MockObject\MockObject $root */
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_READ]));

		$node = new File($root, $this->view, '/bar/foo');
		$node->putContent('bar');
	}

	public function testGetMimeType(): void {
		/** @var \OC\Files\Node\Root|\PHPUnit\Framework\MockObject\MockObject $root */
		$root = $this->getMockBuilder(Root::class)
			->setConstructorArgs([$this->manager, $this->view, $this->user, $this->userMountCache, $this->logger, $this->userManager, $this->eventDispatcher, $this->cacheFactory])
			->getMock();

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['mimetype' => 'text/plain']));

		$node = new File($root, $this->view, '/bar/foo');
		$this->assertEquals('text/plain', $node->getMimeType());
	}

	public function testFOpenRead(): void {
		$stream = fopen('php://memory', 'w+');
		fwrite($stream, 'bar');
		rewind($stream);

		$root = new Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);

		$hook = function ($file): void {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$this->view->expects($this->once())
			->method('fopen')
			->with('/bar/foo', 'r')
			->willReturn($stream);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_ALL]));

		$node = new File($root, $this->view, '/bar/foo');
		$fh = $node->fopen('r');
		$this->assertEquals($stream, $fh);
		$this->assertEquals('bar', fread($fh, 3));
	}

	public function testFOpenWrite(): void {
		$stream = fopen('php://memory', 'w+');

		$root = new Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);
		$hooksCalled = 0;
		$hook = function ($file) use (&$hooksCalled): void {
			$hooksCalled++;
		};

		$root->listen('\OC\Files', 'preWrite', $hook);
		$root->listen('\OC\Files', 'postWrite', $hook);

		$this->view->expects($this->once())
			->method('fopen')
			->with('/bar/foo', 'w')
			->willReturn($stream);

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_ALL]));

		$node = new File($root, $this->view, '/bar/foo');
		$fh = $node->fopen('w');
		$this->assertEquals($stream, $fh);
		fwrite($fh, 'bar');
		rewind($fh);
		$this->assertEquals('bar', fread($stream, 3));
		$this->assertEquals(2, $hooksCalled);
	}


	public function testFOpenReadNotPermitted(): void {
		$this->expectException(NotPermittedException::class);

		$root = new Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);
		$hook = function ($file): void {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => 0]));

		$node = new File($root, $this->view, '/bar/foo');
		$node->fopen('r');
	}


	public function testFOpenReadWriteNoReadPermissions(): void {
		$this->expectException(NotPermittedException::class);

		$root = new Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);
		$hook = function (): void {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_UPDATE]));

		$node = new File($root, $this->view, '/bar/foo');
		$node->fopen('w');
	}


	public function testFOpenReadWriteNoWritePermissions(): void {
		$this->expectException(NotPermittedException::class);

		$root = new Root(
			$this->manager,
			$this->view,
			$this->user,
			$this->userMountCache,
			$this->logger,
			$this->userManager,
			$this->eventDispatcher,
			$this->cacheFactory,
		);
		$hook = function (): void {
			throw new \Exception('Hooks are not supposed to be called');
		};

		$this->view->expects($this->once())
			->method('getFileInfo')
			->with('/bar/foo')
			->willReturn($this->getFileInfo(['permissions' => Constants::PERMISSION_READ]));

		$node = new File($root, $this->view, '/bar/foo');
		$node->fopen('w');
	}
}
