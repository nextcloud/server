<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Upload;

use Generator;
use OCA\DAV\Upload\UploadAutoCollectionPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class UploadAutoCollectionPluginTest extends TestCase {

	private Server&MockObject $server;
	private Tree&MockObject $tree;
	private RequestInterface&MockObject $request;
	private ResponseInterface&MockObject $response;

	public static function dataMissingHeaderShouldReturnTrue(): Generator {
		yield 'missing X-Create-Tree header' => [null];
		yield 'empty X-Create-Tree header' => [''];
		yield 'invalid X-Create-Tree header' => ['true'];
	}

	private static function assertEqualsAndReturn(mixed $expected, mixed $actual, mixed $value) {
		self::assertEquals($expected, $actual);

		return $value;
	}

	public function testBeforeMethodWithRootNodeNotAnICollectionShouldReturnTrue(): void {
		$this->request->method('getHeader')->willReturn('enable');
		$this->request->expects(self::once())
			->method('getPath')
			->willReturn('/non-relevant/path.txt');
		$this->tree->expects(self::once())
			->method('nodeExists')
			->with('/non-relevant')
			->willReturn(false);

		$mockNode = $this->getMockBuilder(INode::class);
		$this->tree->expects(self::once())
			->method('getNodeForPath')
			->willReturn($mockNode);

		$return = $this->plugin->beforeMethod($this->request, $this->response);
		$this->assertTrue($return);
	}

	/**
	 * @dataProvider dataMissingHeaderShouldReturnTrue
	 */
	public function testBeforeMethodWithMissingHeaderShouldReturnTrue(?string $header): void {
		$this->request->expects(self::once())
			->method('getHeader')
			->with('X-Create-Tree')
			->willReturn($header);

		$this->request->expects(self::never())
			->method('getPath');

		$return = $this->plugin->beforeMethod($this->request, $this->response);
		self::assertTrue($return);
	}

	public function testBeforeMethodWithExistingPathShouldReturnTrue(): void {
		$this->request->method('getHeader')->willReturn('enable');
		$this->request->expects(self::once())
			->method('getPath')
			->willReturn('/files/user/deep/image.jpg');
		$this->tree->expects(self::once())
			->method('nodeExists')
			->with('/files/user/deep')
			->willReturn(true);

		$this->tree->expects(self::never())
			->method('getNodeForPath');

		$return = $this->plugin->beforeMethod($this->request, $this->response);
		self::assertTrue($return);
	}

	public function testBeforeMethodShouldSucceed(): void {
		$this->request->method('getHeader')->willReturn('enable');
		$this->request->expects(self::once())
			->method('getPath')
			->willReturn('/files/user/my/deep/path/image.jpg');
		$this->tree->expects(self::once())
			->method('nodeExists')
			->with('/files/user/my/deep/path')
			->willReturn(false);

		$mockNode = $this->createMock(ICollection::class);
		$this->tree->expects(self::once())
			->method('getNodeForPath')
			->with('/files')
			->willReturn($mockNode);
		$mockNode->expects(self::exactly(4))
			->method('childExists')
			->willReturnMap([
				['user', true],
				['my', true],
				['deep', false],
				['path', false],
			]);
		$mockNode->expects(self::exactly(2))
			->method('createDirectory');
		$mockNode->expects(self::exactly(4))
			->method('getChild')
			->willReturn($mockNode);

		$return = $this->plugin->beforeMethod($this->request, $this->response);
		self::assertTrue($return);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);

		$this->server->tree = $this->tree;
		$this->plugin = new UploadAutoCollectionPlugin();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$this->server->httpRequest = $this->request;
		$this->server->httpResponse = $this->response;

		$this->plugin->initialize($this->server);
	}
}
