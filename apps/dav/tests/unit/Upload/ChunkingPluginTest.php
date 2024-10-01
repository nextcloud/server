<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\ChunkingPlugin;
use OCA\DAV\Upload\FutureFile;
use Sabre\DAV\Exception\NotFound;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ChunkingPluginTest extends TestCase {
	/**
	 * @var \Sabre\DAV\Server | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\Tree | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $tree;

	/**
	 * @var ChunkingPlugin
	 */
	private $plugin;
	/** @var RequestInterface | \PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ResponseInterface | \PHPUnit\Framework\MockObject\MockObject */
	private $response;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();

		$this->server->tree = $this->tree;
		$this->plugin = new ChunkingPlugin();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$this->server->httpRequest = $this->request;
		$this->server->httpResponse = $this->response;

		$this->plugin->initialize($this->server);
	}

	public function testBeforeMoveFutureFileSkip(): void {
		$node = $this->createMock(Directory::class);

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('source')
			->willReturn($node);
		$this->response->expects($this->never())
			->method('setStatus');

		$this->assertNull($this->plugin->beforeMove('source', 'target'));
	}

	public function testBeforeMoveDestinationIsDirectory(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);
		$this->expectExceptionMessage('The given destination target is a directory.');

		$sourceNode = $this->createMock(FutureFile::class);
		$targetNode = $this->createMock(Directory::class);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(
				['source'],
				['target'],
			)
			->willReturnOnConsecutiveCalls(
				$sourceNode,
				$targetNode,
			);
		$this->response->expects($this->never())
			->method('setStatus');

		$this->assertNull($this->plugin->beforeMove('source', 'target'));
	}

	public function testBeforeMoveFutureFileSkipNonExisting(): void {
		$sourceNode = $this->createMock(FutureFile::class);
		$sourceNode->expects($this->once())
			->method('getSize')
			->willReturn(4);

		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(
				['source'],
				['target'],
			)
			->willReturnOnConsecutiveCalls(
				$sourceNode,
				$this->throwException(new NotFound()),
			);
		$this->tree->expects($this->any())
			->method('nodeExists')
			->with('target')
			->willReturn(false);
		$this->response->expects($this->once())
			->method('setHeader')
			->with('Content-Length', '0');
		$this->response->expects($this->once())
			->method('setStatus')
			->with(201);
		$this->request->expects($this->once())
			->method('getHeader')
			->with('OC-Total-Length')
			->willReturn(4);

		$this->assertFalse($this->plugin->beforeMove('source', 'target'));
	}

	public function testBeforeMoveFutureFileMoveIt(): void {
		$sourceNode = $this->createMock(FutureFile::class);
		$sourceNode->expects($this->once())
			->method('getSize')
			->willReturn(4);


		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(
				['source'],
				['target'],
			)
			->willReturnOnConsecutiveCalls(
				$sourceNode,
				$this->throwException(new NotFound()),
			);
		$this->tree->expects($this->any())
			->method('nodeExists')
			->with('target')
			->willReturn(true);
		$this->tree->expects($this->once())
			->method('move')
			->with('source', 'target');

		$this->response->expects($this->once())
			->method('setHeader')
			->with('Content-Length', '0');
		$this->response->expects($this->once())
			->method('setStatus')
			->with(204);
		$this->request->expects($this->once())
			->method('getHeader')
			->with('OC-Total-Length')
			->willReturn('4');

		$this->assertFalse($this->plugin->beforeMove('source', 'target'));
	}


	public function testBeforeMoveSizeIsWrong(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);
		$this->expectExceptionMessage('Chunks on server do not sum up to 4 but to 3 bytes');

		$sourceNode = $this->createMock(FutureFile::class);
		$sourceNode->expects($this->once())
			->method('getSize')
			->willReturn(3);


		$this->tree->expects($this->exactly(2))
			->method('getNodeForPath')
			->withConsecutive(
				['source'],
				['target'],
			)
			->willReturnOnConsecutiveCalls(
				$sourceNode,
				$this->throwException(new NotFound()),
			);
		$this->request->expects($this->once())
			->method('getHeader')
			->with('OC-Total-Length')
			->willReturn('4');

		$this->assertFalse($this->plugin->beforeMove('source', 'target'));
	}
}
