<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\WebcalCaching\Plugin;
use OCP\IRequest;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class PluginTest extends \Test\TestCase {
	public function testDisabled(): void {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->once())
			->method('isUserAgent')
			->with(Plugin::ENABLE_FOR_CLIENTS)
			->willReturn(false);

		$request->expects($this->once())
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->willReturn('');

		$plugin = new Plugin($request);

		$this->assertEquals(false, $plugin->isCachingEnabledForThisRequest());
	}

	public function testEnabledUserAgent(): void {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->once())
			->method('isUserAgent')
			->with(Plugin::ENABLE_FOR_CLIENTS)
			->willReturn(true);
		$request->expects($this->once())
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->willReturn('');
		$request->expects($this->once())
			->method('getMethod')
			->willReturn('REPORT');
		$request->expects($this->never())
			->method('getParams');

		$plugin = new Plugin($request);

		$this->assertEquals(true, $plugin->isCachingEnabledForThisRequest());
	}

	public function testEnabledWebcalCachingHeader(): void {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->once())
			->method('isUserAgent')
			->with(Plugin::ENABLE_FOR_CLIENTS)
			->willReturn(false);
		$request->expects($this->once())
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->willReturn('On');
		$request->expects($this->once())
			->method('getMethod')
			->willReturn('REPORT');
		$request->expects($this->never())
			->method('getParams');

		$plugin = new Plugin($request);

		$this->assertEquals(true, $plugin->isCachingEnabledForThisRequest());
	}

	public function testEnabledExportRequest(): void {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->once())
			->method('isUserAgent')
			->with(Plugin::ENABLE_FOR_CLIENTS)
			->willReturn(false);
		$request->expects($this->once())
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->willReturn('');
		$request->expects($this->once())
			->method('getMethod')
			->willReturn('GET');
		$request->expects($this->once())
			->method('getParams')
			->willReturn(['export' => '']);

		$plugin = new Plugin($request);

		$this->assertEquals(true, $plugin->isCachingEnabledForThisRequest());
	}

	public function testSkipNonCalendarRequest(): void {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->once())
			->method('isUserAgent')
			->with(Plugin::ENABLE_FOR_CLIENTS)
			->willReturn(false);

		$request->expects($this->once())
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->willReturn('On');

		$sabreRequest = new Request('REPORT', '/remote.php/dav/principals/users/admin/');
		$sabreRequest->setBaseUrl('/remote.php/dav/');

		$tree = $this->createMock(Tree::class);
		$tree->expects($this->never())
			->method('getNodeForPath');

		$server = new Server($tree);

		$plugin = new Plugin($request);
		$plugin->initialize($server);

		$plugin->beforeMethod($sabreRequest, new Response());
	}

	public function testProcessCalendarRequest(): void {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->once())
			->method('isUserAgent')
			->with(Plugin::ENABLE_FOR_CLIENTS)
			->willReturn(false);

		$request->expects($this->once())
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->willReturn('On');

		$sabreRequest = new Request('REPORT', '/remote.php/dav/calendars/admin/personal/');
		$sabreRequest->setBaseUrl('/remote.php/dav/');

		$tree = $this->createMock(Tree::class);
		$tree->expects($this->once())
			->method('getNodeForPath');

		$server = new Server($tree);

		$plugin = new Plugin($request);
		$plugin->initialize($server);

		$plugin->beforeMethod($sabreRequest, new Response());
	}
}
