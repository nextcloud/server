<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Upload;

use Generator;
use OCA\DAV\Upload\UploadAutoMkcolPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class UploadAutoMkcolPluginTest extends TestCase {
	private Tree&MockObject $tree;
	private RequestInterface&MockObject $request;
	private ResponseInterface&MockObject $response;

	private UploadAutoMkcolPlugin $plugin;

	protected function setUp(): void {
		parent::setUp();

		$server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);

		$server->tree = $this->tree;
		$this->plugin = new UploadAutoMkcolPlugin();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$server->httpRequest = $this->request;
		$server->httpResponse = $this->response;

		$this->plugin->initialize($server);
	}

	public static function dataMissingHeaderShouldReturnTrue(): Generator {
		yield 'missing X-NC-WebDAV-Auto-Mkcol header' => [null];
		yield 'empty X-NC-WebDAV-Auto-Mkcol header' => [''];
		yield 'invalid X-NC-WebDAV-Auto-Mkcol header' => ['enable'];
	}

	public function testBeforeMethodWithRootNodeNotAnICollectionShouldReturnTrue(): void {
		$this->request->method('getHeader')->willReturn('1');
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataMissingHeaderShouldReturnTrue')]
	public function testBeforeMethodWithMissingHeaderShouldReturnTrue(?string $header): void {
		$this->request->expects(self::once())
			->method('getHeader')
			->with('X-NC-WebDAV-Auto-Mkcol')
			->willReturn($header);

		$this->request->expects(self::never())
			->method('getPath');

		$return = $this->plugin->beforeMethod($this->request, $this->response);
		self::assertTrue($return);
	}

	public function testBeforeMethodWithExistingPathShouldReturnTrue(): void {
		$this->request->method('getHeader')->willReturn('1');
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
		$this->request->method('getHeader')->willReturn('1');
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
}
