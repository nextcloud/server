<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\CopyEtagHeaderPlugin;
use OCA\DAV\Connector\Sabre\File;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Test\TestCase;

class CopyEtagHeaderPluginTest extends TestCase {
	private CopyEtagHeaderPlugin $plugin;
	private Server $server;

	protected function setUp(): void {
		parent::setUp();
		$this->server = new \Sabre\DAV\Server();
		$this->plugin = new CopyEtagHeaderPlugin();
		$this->plugin->initialize($this->server);
	}

	public function testCopyEtag(): void {
		$request = new \Sabre\Http\Request('GET', 'dummy.file');
		$response = new \Sabre\Http\Response();
		$response->setHeader('Etag', 'abcd');

		$this->plugin->afterMethod($request, $response);

		$this->assertEquals('abcd', $response->getHeader('OC-Etag'));
	}

	public function testNoopWhenEmpty(): void {
		$request = new \Sabre\Http\Request('GET', 'dummy.file');
		$response = new \Sabre\Http\Response();

		$this->plugin->afterMethod($request, $response);

		$this->assertNull($response->getHeader('OC-Etag'));
	}

	public function testAfterMoveNodeNotFound(): void {
		$tree = $this->createMock(Tree::class);
		$tree->expects(self::once())
			->method('getNodeForPath')
			->with('test.txt')
			->willThrowException(new NotFound());

		$this->server->tree = $tree;
		$this->plugin->afterMove('', 'test.txt');

		// Nothing to assert, we are just testing if the exception is handled
	}

	public function testAfterMove(): void {
		$node = $this->createMock(File::class);
		$node->expects($this->once())
			->method('getETag')
			->willReturn('123456');
		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath')
			->with('test.txt')
			->willReturn($node);

		$this->server->tree = $tree;
		$this->plugin->afterMove('', 'test.txt');

		$this->assertEquals('123456', $this->server->httpResponse->getHeader('OC-Etag'));
		$this->assertEquals('123456', $this->server->httpResponse->getHeader('Etag'));
	}
}
