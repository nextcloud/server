<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Files\Sharing;

use OCA\DAV\Files\Sharing\FilesDropPlugin;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class FilesDropPluginTest extends TestCase {

	private FilesDropPlugin $plugin;

	private Folder&MockObject $node;
	private IShare&MockObject $share;
	private Server&MockObject $server;
	private RequestInterface&MockObject $request;
	private ResponseInterface&MockObject $response;

	protected function setUp(): void {
		parent::setUp();

		$this->node = $this->createMock(Folder::class);
		$this->node->method('getPath')
			->willReturn('/files/token');

		$this->share = $this->createMock(IShare::class);
		$this->share->expects(self::any())
			->method('getNode')
			->willReturn($this->node);
		$this->server = $this->createMock(Server::class);
		$this->plugin = new FilesDropPlugin();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);

		$attributes = $this->createMock(IAttributes::class);
		$this->share->expects($this->any())
			->method('getAttributes')
			->willReturn($attributes);

		$this->share
			->method('getToken')
			->willReturn('token');
	}

	public function testNotEnabled(): void {
		$this->request->expects($this->never())
			->method($this->anything());

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testValid(): void {
		$this->plugin->enable();
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('PUT');

		$this->request->method('getPath')
			->willReturn('/files/token/file.txt');

		$this->request->method('getBaseUrl')
			->willReturn('https://example.com');

		$this->node->expects(self::once())
			->method('getNonExistingName')
			->with('file.txt')
			->willReturn('file.txt');

		$this->request->expects($this->once())
			->method('setUrl')
			->with('https://example.com/files/token/file.txt');

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testFileAlreadyExistsValid(): void {
		$this->plugin->enable();
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('PUT');

		$this->request->method('getPath')
			->willReturn('/files/token/file.txt');

		$this->request->method('getBaseUrl')
			->willReturn('https://example.com');

		$this->node->method('getNonExistingName')
			->with('file.txt')
			->willReturn('file (2).txt');

		$this->request->expects($this->once())
			->method('setUrl')
			->with($this->equalTo('https://example.com/files/token/file (2).txt'));

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testNoMKCOLWithoutNickname(): void {
		$this->plugin->enable();
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('MKCOL');

		$this->expectException(BadRequest::class);

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testMKCOLWithNickname(): void {
		$this->plugin->enable();
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('MKCOL');

		$this->request->method('hasHeader')
			->with('X-NC-Nickname')
			->willReturn(true);
		$this->request->method('getHeader')
			->with('X-NC-Nickname')
			->willReturn('nickname');

		$this->expectNotToPerformAssertions();

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testSubdirPut(): void {
		$this->plugin->enable();
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('PUT');

		$this->request->method('hasHeader')
			->with('X-NC-Nickname')
			->willReturn(true);
		$this->request->method('getHeader')
			->with('X-NC-Nickname')
			->willReturn('nickname');

		$this->request->method('getPath')
			->willReturn('/files/token/folder/file.txt');

		$this->request->method('getBaseUrl')
			->willReturn('https://example.com');

		$nodeName = $this->createMock(Folder::class);
		$nodeFolder = $this->createMock(Folder::class);
		$nodeFolder->expects(self::once())
			->method('getPath')
			->willReturn('/files/token/nickname/folder');
		$nodeFolder->method('getNonExistingName')
			->with('file.txt')
			->willReturn('file.txt');
		$nodeName->expects(self::once())
			->method('get')
			->with('folder')
			->willThrowException(new NotFoundException());
		$nodeName->expects(self::once())
			->method('newFolder')
			->with('folder')
			->willReturn($nodeFolder);

		$this->node->expects(self::once())
			->method('get')
			->willThrowException(new NotFoundException());
		$this->node->expects(self::once())
			->method('newFolder')
			->with('nickname')
			->willReturn($nodeName);

		$this->request->expects($this->once())
			->method('setUrl')
			->with($this->equalTo('https://example.com/files/token/nickname/folder/file.txt'));

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testRecursiveFolderCreation(): void {
		$this->plugin->enable();
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('PUT');
		$this->request->method('hasHeader')
			->with('X-NC-Nickname')
			->willReturn(true);
		$this->request->method('getHeader')
			->with('X-NC-Nickname')
			->willReturn('nickname');

		$this->request->method('getPath')
			->willReturn('/files/token/folder/subfolder/file.txt');
		$this->request->method('getBaseUrl')
			->willReturn('https://example.com');

		$this->request->expects($this->once())
			->method('setUrl')
			->with($this->equalTo('https://example.com/files/token/nickname/folder/subfolder/file.txt'));

		$subfolder = $this->createMock(Folder::class);
		$subfolder->expects(self::once())
			->method('getNonExistingName')
			->with('file.txt')
			->willReturn('file.txt');
		$subfolder->expects(self::once())
			->method('getPath')
			->willReturn('/files/token/nickname/folder/subfolder');

		$folder = $this->createMock(Folder::class);
		$folder->expects(self::once())
			->method('get')
			->with('subfolder')
			->willReturn($subfolder);

		$nickname = $this->createMock(Folder::class);
		$nickname->expects(self::once())
			->method('get')
			->with('folder')
			->willReturn($folder);

		$this->node->method('get')
			->with('nickname')
			->willReturn($nickname);
		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testOnMkcol(): void {
		$this->plugin->enable();
		$this->plugin->setShare($this->share);

		$this->response->expects($this->once())
			->method('setStatus')
			->with(201);

		$response = $this->plugin->onMkcol($this->request, $this->response);
		$this->assertFalse($response);
	}
}
