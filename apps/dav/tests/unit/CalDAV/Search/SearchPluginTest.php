<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Search;

use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\Search\SearchPlugin;
use OCA\DAV\CalDAV\Search\Xml\Request\CalendarSearchReport;
use Sabre\Xml\Service;
use Test\TestCase;

class SearchPluginTest extends TestCase {
	protected $server;

	/** @var SearchPlugin $plugin */
	protected $plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(\Sabre\DAV\Server::class);
		$this->server->tree = $this->createMock(\Sabre\DAV\Tree::class);
		$this->server->httpResponse = $this->createMock(\Sabre\HTTP\Response::class);
		$this->server->xml = new Service();

		$this->plugin = new SearchPlugin();
		$this->plugin->initialize($this->server);
	}

	public function testGetFeatures(): void {
		$this->assertEquals(['nc-calendar-search'], $this->plugin->getFeatures());
	}

	public function testGetName(): void {
		$this->assertEquals('nc-calendar-search', $this->plugin->getPluginName());
	}

	public function testInitialize(): void {
		$server = $this->createMock(\Sabre\DAV\Server::class);

		$plugin = new SearchPlugin();

		$server->expects($this->once())
			->method('on')
			->with('report', [$plugin, 'report']);
		$server->xml = new Service();

		$plugin->initialize($server);

		$this->assertEquals(
			$server->xml->elementMap['{http://nextcloud.com/ns}calendar-search'],
			'OCA\\DAV\\CalDAV\\Search\\Xml\\Request\\CalendarSearchReport'
		);
	}

	public function testReportUnknown(): void {
		$result = $this->plugin->report('{urn:ietf:params:xml:ns:caldav}calendar-query', 'REPORT', null);
		$this->assertEquals($result, null);
		$this->assertNotEquals($this->server->transactionType, 'report-nc-calendar-search');
	}

	public function testReport(): void {
		$report = $this->createMock(CalendarSearchReport::class);
		$report->filters = [];
		$calendarHome = $this->createMock(CalendarHome::class);
		$this->server->expects($this->once())
			->method('getRequestUri')
			->with()
			->willReturn('/re/quest/u/r/i');
		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/re/quest/u/r/i')
			->willReturn($calendarHome);
		$this->server->expects($this->once())
			->method('getHTTPDepth')
			->with(2)
			->willReturn(2);
		$this->server
			->method('getHTTPPrefer')
			->willReturn([
				'return' => null
			]);
		$calendarHome->expects($this->once())
			->method('calendarSearch')
			->willReturn([]);

		$this->plugin->report('{http://nextcloud.com/ns}calendar-search', $report, '');
	}

	public function testSupportedReportSetNoCalendarHome(): void {
		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/foo/bar')
			->willReturn(null);

		$reports = $this->plugin->getSupportedReportSet('/foo/bar');
		$this->assertEquals([], $reports);
	}

	public function testSupportedReportSet(): void {
		$calendarHome = $this->createMock(CalendarHome::class);

		$this->server->tree->expects($this->once())
			->method('getNodeForPath')
			->with('/bar/foo')
			->willReturn($calendarHome);

		$reports = $this->plugin->getSupportedReportSet('/bar/foo');
		$this->assertEquals([
			'{http://nextcloud.com/ns}calendar-search'
		], $reports);
	}
}
