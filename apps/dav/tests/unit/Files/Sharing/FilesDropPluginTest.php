<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\Files\Sharing;

use OC\Files\View;
use OCA\DAV\Files\Sharing\FilesDropPlugin;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class FilesDropPluginTest extends TestCase {

	/** @var View|\PHPUnit\Framework\MockObject\MockObject */
	private $view;

	/** @var IShare|\PHPUnit\Framework\MockObject\MockObject */
	private $share;

	/** @var Server|\PHPUnit\Framework\MockObject\MockObject */
	private $server;

	/** @var FilesDropPlugin */
	private $plugin;

	/** @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject */
	private $response;

	protected function setUp(): void {
		parent::setUp();

		$this->view = $this->createMock(View::class);
		$this->share = $this->createMock(IShare::class);
		$this->server = $this->createMock(Server::class);
		$this->plugin = new FilesDropPlugin();

		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);

		$this->response->expects($this->never())
			->method($this->anything());

		$attributes = $this->createMock(IAttributes::class);
		$this->share->expects($this->any())
			->method('getAttributes')
			->willReturn($attributes);
	}

	public function testInitialize(): void {
		$this->server->expects($this->once())
			->method('on')
			->with(
				$this->equalTo('beforeMethod:*'),
				$this->equalTo([$this->plugin, 'beforeMethod']),
				$this->equalTo(999)
			);

		$this->plugin->initialize($this->server);
	}

	public function testNotEnabled(): void {
		$this->view->expects($this->never())
			->method($this->anything());

		$this->request->expects($this->never())
			->method($this->anything());

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testValid(): void {
		$this->plugin->enable();
		$this->plugin->setView($this->view);
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('PUT');

		$this->request->method('getPath')
			->willReturn('file.txt');

		$this->request->method('getBaseUrl')
			->willReturn('https://example.com');

		$this->view->method('file_exists')
			->with('/file.txt')
			->willReturn(false);

		$this->request->expects($this->once())
			->method('setUrl')
			->with('https://example.com/file.txt');

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testFileAlreadyExistsValid(): void {
		$this->plugin->enable();
		$this->plugin->setView($this->view);
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('PUT');

		$this->request->method('getPath')
			->willReturn('file.txt');

		$this->request->method('getBaseUrl')
			->willReturn('https://example.com');

		$this->view->method('file_exists')
			->willReturnCallback(function ($path) {
				if ($path === 'file.txt' || $path === '/file.txt') {
					return true;
				} else {
					return false;
				}
			});

		$this->request->expects($this->once())
			->method('setUrl')
			->with($this->equalTo('https://example.com/file (2).txt'));

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testNoMKCOL(): void {
		$this->plugin->enable();
		$this->plugin->setView($this->view);
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('MKCOL');

		$this->expectException(MethodNotAllowed::class);

		$this->plugin->beforeMethod($this->request, $this->response);
	}

	public function testNoSubdirPut(): void {
		$this->plugin->enable();
		$this->plugin->setView($this->view);
		$this->plugin->setShare($this->share);

		$this->request->method('getMethod')
			->willReturn('PUT');

		$this->request->method('getPath')
			->willReturn('folder/file.txt');

		$this->request->method('getBaseUrl')
			->willReturn('https://example.com');

		$this->view->method('file_exists')
			->willReturnCallback(function ($path) {
				if ($path === 'file.txt' || $path === '/file.txt') {
					return true;
				} else {
					return false;
				}
			});

		$this->request->expects($this->once())
			->method('setUrl')
			->with($this->equalTo('https://example.com/file (2).txt'));

		$this->plugin->beforeMethod($this->request, $this->response);
	}
}
