<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\WebcalCaching\Connection;
use OCA\DAV\CalDAV\WebcalCaching\RefreshWebcalService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject;
use Sabre\VObject\Recur\NoInstancesException;

use Test\TestCase;

class RefreshWebcalServiceTest extends TestCase {
	private CalDavBackend|MockObject $caldavBackend;
	private Connection|MockObject $connection;
	private LoggerInterface|MockObject $logger;
	private ITimeFactory|MockObject $time;

	protected function setUp(): void {
		parent::setUp();

		$this->caldavBackend = $this->createMock(CalDavBackend::class);
		$this->connection = $this->createMock(Connection::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->time = $this->createMock(ITimeFactory::class);
	}

	/**
	 * @param string $body
	 * @param string $contentType
	 * @param string $result
	 *
	 * @dataProvider runDataProvider
	 */
	public function testRun(string $body, string $contentType, string $result): void {
		$refreshWebcalService = $this->getMockBuilder(RefreshWebcalService::class)
			->onlyMethods(['getRandomCalendarObjectUri'])
			->setConstructorArgs([$this->caldavBackend, $this->logger, $this->connection, $this->time])
			->getMock();

		$refreshWebcalService
			->method('getRandomCalendarObjectUri')
			->willReturn('uri-1.ics');

		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->willReturn([
				[
					'id' => '99',
					'uri' => 'sub456',
					RefreshWebcalService::REFRESH_RATE => 'P1D',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla',
					'lastmodified' => 0,
				],
				[
					'id' => '42',
					'uri' => 'sub123',
					RefreshWebcalService::REFRESH_RATE => 'PT1H',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla2',
					'lastmodified' => 0,
				],
			]);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn($result);
		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->with(42, 'uri-1.ics', $result, 1);

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	/**
	 * @param string $body
	 * @param string $contentType
	 * @param string $result
	 *
	 * @dataProvider identicalDataProvider
	 */
	public function testRunIdentical(string $uid, array $calendarObject, string $body, string $contentType, string $result): void {
		$refreshWebcalService = $this->getMockBuilder(RefreshWebcalService::class)
			->onlyMethods(['getRandomCalendarObjectUri'])
			->setConstructorArgs([$this->caldavBackend, $this->logger, $this->connection, $this->time])
			->getMock();

		$refreshWebcalService
			->method('getRandomCalendarObjectUri')
			->willReturn('uri-1.ics');

		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->willReturn([
				[
					'id' => '99',
					'uri' => 'sub456',
					RefreshWebcalService::REFRESH_RATE => 'P1D',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla',
					'lastmodified' => 0,
				],
				[
					'id' => '42',
					'uri' => 'sub123',
					RefreshWebcalService::REFRESH_RATE => 'PT1H',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla2',
					'lastmodified' => 0,
				],
			]);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn($result);

		$this->caldavBackend->expects(self::once())
			->method('getLimitedCalendarObjects')
			->willReturn($calendarObject);

		$denormalised = [
			'etag' => 100,
			'size' => strlen($calendarObject[$uid]['calendardata']),
			'uid' => 'sub456'
		];

		$this->caldavBackend->expects(self::once())
			->method('getDenormalizedData')
			->willReturn($denormalised);

		$this->caldavBackend->expects(self::never())
			->method('createCalendarObject');

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub456');
	}

	public function testRunJustUpdated(): void {
		$refreshWebcalService = $this->getMockBuilder(RefreshWebcalService::class)
			->onlyMethods(['getRandomCalendarObjectUri'])
			->setConstructorArgs([$this->caldavBackend, $this->logger, $this->connection, $this->time])
			->getMock();

		$refreshWebcalService
			->method('getRandomCalendarObjectUri')
			->willReturn('uri-1.ics');

		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->willReturn([
				[
					'id' => '99',
					'uri' => 'sub456',
					RefreshWebcalService::REFRESH_RATE => 'P1D',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla',
					'lastmodified' => time(),
				],
				[
					'id' => '42',
					'uri' => 'sub123',
					RefreshWebcalService::REFRESH_RATE => 'PT1H',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla2',
					'lastmodified' => time(),
				],
			]);

		$timeMock = $this->createMock(\DateTime::class);
		$this->time->expects(self::once())
			->method('getDateTime')
			->willReturn($timeMock);
		$timeMock->expects(self::once())
			->method('getTimestamp')
			->willReturn(2101724667);
		$this->time->expects(self::once())
			->method('getTime')
			->willReturn(time());
		$this->connection->expects(self::never())
			->method('queryWebcalFeed');
		$this->caldavBackend->expects(self::never())
			->method('createCalendarObject');

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	/**
	 * @param string $body
	 * @param string $contentType
	 * @param string $result
	 *
	 * @dataProvider runDataProvider
	 */
	public function testRunCreateCalendarNoException(string $body, string $contentType, string $result): void {
		$refreshWebcalService = $this->getMockBuilder(RefreshWebcalService::class)
			->onlyMethods(['getRandomCalendarObjectUri', 'getSubscription',])
			->setConstructorArgs([$this->caldavBackend, $this->logger, $this->connection, $this->time])
			->getMock();

		$refreshWebcalService
			->method('getRandomCalendarObjectUri')
			->willReturn('uri-1.ics');

		$refreshWebcalService
			->method('getSubscription')
			->willReturn([
				'id' => '42',
				'uri' => 'sub123',
				RefreshWebcalService::REFRESH_RATE => 'PT1H',
				RefreshWebcalService::STRIP_TODOS => '1',
				RefreshWebcalService::STRIP_ALARMS => '1',
				RefreshWebcalService::STRIP_ATTACHMENTS => '1',
				'source' => 'webcal://foo.bar/bla2',
				'lastmodified' => 0,
			]);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn($result);

		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->with(42, 'uri-1.ics', $result, 1);

		$noInstanceException = new NoInstancesException("can't add calendar object");
		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->willThrowException($noInstanceException);

		$this->logger->expects(self::once())
			->method('warning')
			->with('Unable to create calendar object from subscription {subscriptionId}', ['exception' => $noInstanceException, 'subscriptionId' => '42', 'source' => 'webcal://foo.bar/bla2']);

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	/**
	 * @param string $body
	 * @param string $contentType
	 * @param string $result
	 *
	 * @dataProvider runDataProvider
	 */
	public function testRunCreateCalendarBadRequest(string $body, string $contentType, string $result): void {
		$refreshWebcalService = $this->getMockBuilder(RefreshWebcalService::class)
			->onlyMethods(['getRandomCalendarObjectUri', 'getSubscription'])
			->setConstructorArgs([$this->caldavBackend, $this->logger, $this->connection, $this->time])
			->getMock();

		$refreshWebcalService
			->method('getRandomCalendarObjectUri')
			->willReturn('uri-1.ics');

		$refreshWebcalService
			->method('getSubscription')
			->willReturn([
				'id' => '42',
				'uri' => 'sub123',
				RefreshWebcalService::REFRESH_RATE => 'PT1H',
				RefreshWebcalService::STRIP_TODOS => '1',
				RefreshWebcalService::STRIP_ALARMS => '1',
				RefreshWebcalService::STRIP_ATTACHMENTS => '1',
				'source' => 'webcal://foo.bar/bla2',
				'lastmodified' => 0,
			]);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn($result);

		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->with(42, 'uri-1.ics', $result, 1);

		$badRequestException = new BadRequest("can't add reach calendar url");
		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->willThrowException($badRequestException);

		$this->logger->expects(self::once())
			->method('warning')
			->with('Unable to create calendar object from subscription {subscriptionId}', ['exception' => $badRequestException, 'subscriptionId' => '42', 'source' => 'webcal://foo.bar/bla2']);

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	/**
	 * @return array
	 */
	public static function identicalDataProvider():array {
		return [
			[
				'12345',
				[
					'12345' => [
						'id' => 42,
						'etag' => 100,
						'uri' => 'sub456',
						'calendardata' => "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 4.1.1//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
					],
				],
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 4.1.1//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				'text/calendar;charset=utf8',
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 4.1.1//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20180218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
			],
		];
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
}
