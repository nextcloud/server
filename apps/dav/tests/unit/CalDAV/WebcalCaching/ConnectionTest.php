<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\WebcalCaching\Connection;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\Http\Client\LocalServerException;
use OCP\IAppConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

use Test\TestCase;

class ConnectionTest extends TestCase {

	private IClientService|MockObject $clientService;
	private IConfig|MockObject $config;
	private LoggerInterface|MockObject $logger;
	private Connection $connection;

	public function setUp(): void {
		$this->clientService = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->connection = new Connection($this->clientService, $this->config, $this->logger);
	}

	/**
	 * @dataProvider runLocalURLDataProvider
	 */
	public function testLocalUrl($source): void {
		$subscription = [
			'id' => 42,
			'uri' => 'sub123',
			'refreshreate' => 'P1H',
			'striptodos' => 1,
			'stripalarms' => 1,
			'stripattachments' => 1,
			'source' => $source,
			'lastmodified' => 0,
		];

		$client = $this->createMock(IClient::class);
		$this->clientService->expects(self::once())
			->method('newClient')
			->with()
			->willReturn($client);

		$this->config->expects(self::once())
			->method('getValueString')
			->with('dav', 'webcalAllowLocalAccess', 'no')
			->willReturn('no');

		$localServerException = new LocalServerException();
		$client->expects(self::once())
			->method('get')
			->willThrowException($localServerException);
		$this->logger->expects(self::once())
			->method('warning')
			->with('Subscription 42 was not refreshed because it violates local access rules', ['exception' => $localServerException]);

		$this->connection->queryWebcalFeed($subscription);
	}

	public function testInvalidUrl(): void {
		$subscription = [
			'id' => 42,
			'uri' => 'sub123',
			'refreshreate' => 'P1H',
			'striptodos' => 1,
			'stripalarms' => 1,
			'stripattachments' => 1,
			'source' => '!@#$',
			'lastmodified' => 0,
		];

		$client = $this->createMock(IClient::class);
		$this->config->expects(self::never())
			->method('getValueString');
		$client->expects(self::never())
			->method('get');

		$this->connection->queryWebcalFeed($subscription);

	}

	/**
	 * @param string $result
	 * @param string $contentType
	 * @dataProvider urlDataProvider
	 */
	public function testConnection(string $url, string $result, string $contentType): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$subscription = [
			'id' => 42,
			'uri' => 'sub123',
			'refreshreate' => 'P1H',
			'striptodos' => 1,
			'stripalarms' => 1,
			'stripattachments' => 1,
			'source' => $url,
			'lastmodified' => 0,
		];

		$this->clientService->expects($this->once())
			->method('newClient')
			->with()
			->willReturn($client);

		$this->config->expects($this->once())
			->method('getValueString')
			->with('dav', 'webcalAllowLocalAccess', 'no')
			->willReturn('no');

		$client->expects($this->once())
			->method('get')
			->with('https://foo.bar/bla2')
			->willReturn($response);

		$response->expects($this->once())
			->method('getBody')
			->with()
			->willReturn($result);
		$response->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn($contentType);

		$this->connection->queryWebcalFeed($subscription);
	}

	public static function runLocalURLDataProvider(): array {
		return [
			['localhost/foo.bar'],
			['localHost/foo.bar'],
			['random-host/foo.bar'],
			['[::1]/bla.blub'],
			['[::]/bla.blub'],
			['192.168.0.1'],
			['172.16.42.1'],
			['[fdf8:f53b:82e4::53]/secret.ics'],
			['[fe80::200:5aee:feaa:20a2]/secret.ics'],
			['[0:0:0:0:0:0:10.0.0.1]/secret.ics'],
			['[0:0:0:0:0:ffff:127.0.0.0]/secret.ics'],
			['10.0.0.1'],
			['another-host.local'],
			['service.localhost'],
		];
	}

	public static function urlDataProvider(): array {
		return [
			[
				'https://foo.bar/bla2',
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 4.1.1//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				'text/calendar;charset=utf8',
			],
			[
				'https://foo.bar/bla2',
				'["vcalendar",[["prodid",{},"text","-//Example Corp.//Example Client//EN"],["version",{},"text","2.0"]],[["vtimezone",[["last-modified",{},"date-time","2004-01-10T03:28:45Z"],["tzid",{},"text","US/Eastern"]],[["daylight",[["dtstart",{},"date-time","2000-04-04T02:00:00"],["rrule",{},"recur",{"freq":"YEARLY","byday":"1SU","bymonth":4}],["tzname",{},"text","EDT"],["tzoffsetfrom",{},"utc-offset","-05:00"],["tzoffsetto",{},"utc-offset","-04:00"]],[]],["standard",[["dtstart",{},"date-time","2000-10-26T02:00:00"],["rrule",{},"recur",{"freq":"YEARLY","byday":"1SU","bymonth":10}],["tzname",{},"text","EST"],["tzoffsetfrom",{},"utc-offset","-04:00"],["tzoffsetto",{},"utc-offset","-05:00"]],[]]]],["vevent",[["dtstamp",{},"date-time","2006-02-06T00:11:21Z"],["dtstart",{"tzid":"US/Eastern"},"date-time","2006-01-02T14:00:00"],["duration",{},"duration","PT1H"],["recurrence-id",{"tzid":"US/Eastern"},"date-time","2006-01-04T12:00:00"],["summary",{},"text","Event #2"],["uid",{},"text","12345"]],[]]]]',
				'application/calendar+json',
			],
			[
				'https://foo.bar/bla2',
				'<?xml version="1.0" encoding="utf-8" ?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0"><vcalendar><properties><prodid><text>-//Example Inc.//Example Client//EN</text></prodid><version><text>2.0</text></version></properties><components><vevent><properties><dtstamp><date-time>2006-02-06T00:11:21Z</date-time></dtstamp><dtstart><parameters><tzid><text>US/Eastern</text></tzid></parameters><date-time>2006-01-04T14:00:00</date-time></dtstart><duration><duration>PT1H</duration></duration><recurrence-id><parameters><tzid><text>US/Eastern</text></tzid></parameters><date-time>2006-01-04T12:00:00</date-time></recurrence-id><summary><text>Event #2 bis</text></summary><uid><text>12345</text></uid></properties></vevent></components></vcalendar></icalendar>',
				'application/calendar+xml',
			],
		];
	}
}
