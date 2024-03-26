<?php
/**
 * @copyright Copyright (c) 2018 Georg Ehrke
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
