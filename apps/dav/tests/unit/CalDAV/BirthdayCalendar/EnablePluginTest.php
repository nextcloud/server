<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\BirthdayCalendar;

use OCA\DAV\CalDAV\BirthdayCalendar\EnablePlugin;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCP\IConfig;
use OCP\IUser;
use Test\TestCase;

class EnablePluginTest extends TestCase {

	/** @var \Sabre\DAV\Server|\PHPUnit\Framework\MockObject\MockObject */
	protected $server;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var BirthdayService |\PHPUnit\Framework\MockObject\MockObject */
	protected $birthdayService;

	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	protected $user;

	/** @var EnablePlugin $plugin */
	protected $plugin;

	protected $request;

	protected $response;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(\Sabre\DAV\Server::class);
		$this->server->tree = $this->createMock(\Sabre\DAV\Tree::class);
		$this->server->httpResponse = $this->createMock(\Sabre\HTTP\Response::class);
		$this->server->xml = $this->createMock(\Sabre\DAV\Xml\Service::class);

		$this->config = $this->createMock(IConfig::class);
		$this->birthdayService = $this->createMock(BirthdayService::class);
		$this->user = $this->createMock(IUser::class);

		$this->plugin = new EnablePlugin($this->config, $this->birthdayService, $this->user);
		$this->plugin->initialize($this->server);

		$this->request = $this->createMock(\Sabre\HTTP\RequestInterface::class);
		$this->response = $this->createMock(\Sabre\HTTP\ResponseInterface::class);
	}

	public function testGetFeatures(): void {
		$this->assertEquals(['nc-enable-birthday-calendar'], $this->plugin->getFeatures());
	}

	public function testGetName(): void {
		$this->assertEquals('nc-enable-birthday-calendar', $this->plugin->getPluginName());
	}

	public function testInitialize(): void {
		$server = $this->createMock(\Sabre\DAV\Server::class);

		$plugin = new EnablePlugin($this->config, $this->birthdayService, $this->user);

		$server->expects($this->once())
			->method('on')
			->with('method:POST', [$plugin, 'httpPost']);

		$plugin->initialize($server);
	}

	public function testHttpPostNoCalendarHome(): void {
		$calendar = $this->createMock(Calendar::class);

		$this->server->expects($this->once())
			->method('getRequestUri')
			->willReturn('/bar/foo');
		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/bar/foo')
			->willReturn($calendar);

		$this->config->expects($this->never())
			->method('setUserValue');

		$this->birthdayService->expects($this->never())
			->method('syncUser');

		$this->plugin->httpPost($this->request, $this->response);
	}

	public function testHttpPostWrongRequest(): void {
		$calendarHome = $this->createMock(CalendarHome::class);

		$this->server->expects($this->once())
			->method('getRequestUri')
			->willReturn('/bar/foo');
		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/bar/foo')
			->willReturn($calendarHome);

		$this->request->expects($this->once())
			->method('getBodyAsString')
			->willReturn('<nc:disable-birthday-calendar xmlns:nc="http://nextcloud.com/ns"/>');

		$this->request->expects($this->once())
			->method('getUrl')
			->willReturn('url_abc');

		$this->server->xml->expects($this->once())
			->method('parse')
			->willReturnCallback(function ($requestBody, $url, &$documentType): void {
				$documentType = '{http://nextcloud.com/ns}disable-birthday-calendar';
			});

		$this->config->expects($this->never())
			->method('setUserValue');

		$this->birthdayService->expects($this->never())
			->method('syncUser');

		$this->plugin->httpPost($this->request, $this->response);
	}

	public function testHttpPostNotAuthorized(): void {
		$calendarHome = $this->createMock(CalendarHome::class);

		$this->server->expects($this->once())
			->method('getRequestUri')
			->willReturn('/bar/foo');
		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/bar/foo')
			->willReturn($calendarHome);

		$calendarHome->expects($this->once())
			->method('getOwner')
			->willReturn('principals/users/BlaBlub');

		$this->request->expects($this->once())
			->method('getBodyAsString')
			->willReturn('<nc:enable-birthday-calendar xmlns:nc="http://nextcloud.com/ns"/>');

		$this->request->expects($this->once())
			->method('getUrl')
			->willReturn('url_abc');

		$this->server->xml->expects($this->once())
			->method('parse')
			->willReturnCallback(function ($requestBody, $url, &$documentType): void {
				$documentType = '{http://nextcloud.com/ns}enable-birthday-calendar';
			});

		$this->user->expects(self::once())
			->method('getUID')
			->willReturn('admin');

		$this->server->httpResponse->expects($this->once())
			->method('setStatus')
			->with(403);

		$this->config->expects($this->never())
			->method('setUserValue');

		$this->birthdayService->expects($this->never())
			->method('syncUser');


		$result = $this->plugin->httpPost($this->request, $this->response);

		$this->assertEquals(false, $result);
	}

	public function testHttpPost(): void {
		$calendarHome = $this->createMock(CalendarHome::class);

		$this->server->expects($this->once())
			->method('getRequestUri')
			->willReturn('/bar/foo');
		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/bar/foo')
			->willReturn($calendarHome);

		$calendarHome->expects($this->once())
			->method('getOwner')
			->willReturn('principals/users/BlaBlub');

		$this->request->expects($this->once())
			->method('getBodyAsString')
			->willReturn('<nc:enable-birthday-calendar xmlns:nc="http://nextcloud.com/ns"/>');

		$this->request->expects($this->once())
			->method('getUrl')
			->willReturn('url_abc');

		$this->server->xml->expects($this->once())
			->method('parse')
			->willReturnCallback(function ($requestBody, $url, &$documentType): void {
				$documentType = '{http://nextcloud.com/ns}enable-birthday-calendar';
			});

		$this->user->expects(self::exactly(3))
			->method('getUID')
			->willReturn('BlaBlub');

		$this->config->expects($this->once())
			->method('setUserValue')
			->with('BlaBlub', 'dav', 'generateBirthdayCalendar', 'yes');

		$this->birthdayService->expects($this->once())
			->method('syncUser')
			->with('BlaBlub');

		$this->server->httpResponse->expects($this->once())
			->method('setStatus')
			->with(204);

		$result = $this->plugin->httpPost($this->request, $this->response);

		$this->assertEquals(false, $result);
	}
}
