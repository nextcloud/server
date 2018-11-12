<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use GuzzleHttp\HandlerStack;
use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\ILogger;
use Test\TestCase;

use Sabre\VObject;

class RefreshWebcalJobTest extends TestCase {

	/** @var CalDavBackend | \PHPUnit_Framework_MockObject_MockObject */
	private $caldavBackend;

	/** @var IClientService | \PHPUnit_Framework_MockObject_MockObject */
	private $clientService;

	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var ITimeFactory | \PHPUnit_Framework_MockObject_MockObject */
	private $timeFactory;

	/** @var IJobList | \PHPUnit_Framework_MockObject_MockObject */
	private $jobList;

	protected function setUp() {
		parent::setUp();

		$this->caldavBackend = $this->createMock(CalDavBackend::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->jobList = $this->createMock(IJobList::class);
	}

	/**
	 * @param string $body
	 * @param string $contentType
	 * @param string $result
	 *
	 * @dataProvider runDataProvider
	 */
	public function testRun(string $body, string $contentType, string $result) {
		$backgroundJob = new RefreshWebcalJob($this->caldavBackend,
			$this->clientService, $this->config, $this->logger, $this->timeFactory);

		$backgroundJob->setArgument([
			'principaluri' => 'principals/users/testuser',
			'uri' => 'sub123',
		]);
		$backgroundJob->setLastRun(0);

		$this->timeFactory->expects($this->once())
			->method('getTime')
			->with()
			->will($this->returnValue(1000000000));

		$this->caldavBackend->expects($this->exactly(2))
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->will($this->returnValue([
				[
					'id' => 99,
					'uri' => 'sub456',
					'refreshreate' => 'P1D',
					'striptodos' => 1,
					'stripalarms' => 1,
					'stripattachments' => 1,
					'source' => 'webcal://foo.bar/bla'
				],
				[
					'id' => 42,
					'uri' => 'sub123',
					'refreshreate' => 'P1H',
					'striptodos' => 1,
					'stripalarms' => 1,
					'stripattachments' => 1,
					'source' => 'webcal://foo.bar/bla2'
				],
			]));

		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->with()
			->will($this->returnValue($client));

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'webcalAllowLocalAccess', 'no')
			->will($this->returnValue('no'));

		$client->expects($this->once())
			->method('get')
			->with('https://foo.bar/bla2', $this->callback(function($obj) {
				return $obj['allow_redirects']['redirects'] === 10 && $obj['handler'] instanceof HandlerStack;
			}))
			->will($this->returnValue($response));

		$response->expects($this->once())
			->method('getBody')
			->with()
			->will($this->returnValue($body));
		$response->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue($contentType));

		$this->caldavBackend->expects($this->once())
			->method('purgeAllCachedEventsForSubscription')
			->with(42);

		$this->caldavBackend->expects($this->once())
			->method('createCalendarObject')
			->with(42, '12345.ics', $result, 1);

		$backgroundJob->execute($this->jobList, $this->logger);
	}

	/**
	 * @return array
	 */
	public function runDataProvider():array {
		return [
			[
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 4.1.1//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				'text/calendar;charset=utf8',
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
			],
			[
				'["vcalendar",[["prodid",{},"text","-//Example Corp.//Example Client//EN"],["version",{},"text","2.0"]],[["vtimezone",[["last-modified",{},"date-time","2004-01-10T03:28:45Z"],["tzid",{},"text","US/Eastern"]],[["daylight",[["dtstart",{},"date-time","2000-04-04T02:00:00"],["rrule",{},"recur",{"freq":"YEARLY","byday":"1SU","bymonth":4}],["tzname",{},"text","EDT"],["tzoffsetfrom",{},"utc-offset","-05:00"],["tzoffsetto",{},"utc-offset","-04:00"]],[]],["standard",[["dtstart",{},"date-time","2000-10-26T02:00:00"],["rrule",{},"recur",{"freq":"YEARLY","byday":"1SU","bymonth":10}],["tzname",{},"text","EST"],["tzoffsetfrom",{},"utc-offset","-04:00"],["tzoffsetto",{},"utc-offset","-05:00"]],[]]]],["vevent",[["dtstamp",{},"date-time","2006-02-06T00:11:21Z"],["dtstart",{"tzid":"US/Eastern"},"date-time","2006-01-02T14:00:00"],["duration",{},"duration","PT1H"],["recurrence-id",{"tzid":"US/Eastern"},"date-time","2006-01-04T12:00:00"],["summary",{},"text","Event #2"],["uid",{},"text","12345"]],[]]]]',
				'application/calendar+json',
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VTIMEZONE\r\nLAST-MODIFIED:20040110T032845Z\r\nTZID:US/Eastern\r\nBEGIN:DAYLIGHT\r\nDTSTART:20000404T020000\r\nRRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4\r\nTZNAME:EDT\r\nTZOFFSETFROM:-0500\r\nTZOFFSETTO:-0400\r\nEND:DAYLIGHT\r\nBEGIN:STANDARD\r\nDTSTART:20001026T020000\r\nRRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=10\r\nTZNAME:EST\r\nTZOFFSETFROM:-0400\r\nTZOFFSETTO:-0500\r\nEND:STANDARD\r\nEND:VTIMEZONE\r\nBEGIN:VEVENT\r\nDTSTAMP:20060206T001121Z\r\nDTSTART;TZID=US/Eastern:20060102T140000\r\nDURATION:PT1H\r\nRECURRENCE-ID;TZID=US/Eastern:20060104T120000\r\nSUMMARY:Event #2\r\nUID:12345\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"
			],
			[
				'<?xml version="1.0" encoding="utf-8" ?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0"><vcalendar><properties><prodid><text>-//Example Inc.//Example Client//EN</text></prodid><version><text>2.0</text></version></properties><components><vevent><properties><dtstamp><date-time>2006-02-06T00:11:21Z</date-time></dtstamp><dtstart><parameters><tzid><text>US/Eastern</text></tzid></parameters><date-time>2006-01-04T14:00:00</date-time></dtstart><duration><duration>PT1H</duration></duration><recurrence-id><parameters><tzid><text>US/Eastern</text></tzid></parameters><date-time>2006-01-04T12:00:00</date-time></recurrence-id><summary><text>Event #2 bis</text></summary><uid><text>12345</text></uid></properties></vevent></components></vcalendar></icalendar>',
				'application/calendar+xml',
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nDTSTAMP:20060206T001121Z\r\nDTSTART;TZID=US/Eastern:20060104T140000\r\nDURATION:PT1H\r\nRECURRENCE-ID;TZID=US/Eastern:20060104T120000\r\nSUMMARY:Event #2 bis\r\nUID:12345\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n"
			]
		];
	}

	/**
	 * @dataProvider runLocalURLDataProvider
	 *
	 * @param string $source
	 */
	public function testRunLocalURL($source) {
		$backgroundJob = new RefreshWebcalJob($this->caldavBackend,
			$this->clientService, $this->config, $this->logger, $this->timeFactory);

		$backgroundJob->setArgument([
			'principaluri' => 'principals/users/testuser',
			'uri' => 'sub123',
		]);
		$backgroundJob->setLastRun(0);

		$this->timeFactory->expects($this->once())
			->method('getTime')
			->with()
			->will($this->returnValue(1000000000));

		$this->caldavBackend->expects($this->exactly(2))
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->will($this->returnValue([
				[
					'id' => 42,
					'uri' => 'sub123',
					'refreshreate' => 'P1H',
					'striptodos' => 1,
					'stripalarms' => 1,
					'stripattachments' => 1,
					'source' => $source
				],
			]));

		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->with()
			->will($this->returnValue($client));

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'webcalAllowLocalAccess', 'no')
			->will($this->returnValue('no'));

		$client->expects($this->never())
			->method('get');

		$backgroundJob->execute($this->jobList, $this->logger);
	}

	public function runLocalURLDataProvider():array {
		return [
			['localhost/foo.bar'],
			['[::1]/bla.blub'],
			['192.168.0.1'],
			['10.0.0.1'],
			['another-host.local'],
			['service.localhost'],
			['!@#$'], // test invalid url
		];
	}
}
