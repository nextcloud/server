<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Import\ImportService;
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
	private CalDavBackend&MockObject $caldavBackend;
	private Connection&MockObject $connection;
	private LoggerInterface&MockObject $logger;
	private ImportService&MockObject $importService;
	private ITimeFactory&MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->caldavBackend = $this->createMock(CalDavBackend::class);
		$this->connection = $this->createMock(Connection::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->importService = $this->createMock(ImportService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		// Default time factory behavior: current time is far in the future so refresh always happens
		$this->timeFactory->method('getTime')->willReturn(PHP_INT_MAX);
		$this->timeFactory->method('getDateTime')->willReturn(new \DateTime());
	}

	/**
	 * Helper to create a resource stream from string content
	 */
	private function createStreamFromString(string $content) {
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $content);
		rewind($stream);
		return $stream;
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'runDataProvider')]
	public function testRun(string $body, string $format, string $result): void {
		$refreshWebcalService = new RefreshWebcalService(
			$this->caldavBackend,
			$this->logger,
			$this->connection,
			$this->timeFactory,
			$this->importService
		);

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

		$stream = $this->createStreamFromString($body);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn(['data' => $stream, 'format' => $format]);

		$this->caldavBackend->expects(self::once())
			->method('getLimitedCalendarObjects')
			->willReturn([]);

		// Create a VCalendar object that will be yielded by the import service
		$vCalendar = VObject\Reader::read($result);

		$generator = function () use ($vCalendar) {
			yield $vCalendar;
		};

		$this->importService->expects(self::once())
			->method('importText')
			->willReturn($generator());

		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->with(
				'42',
				self::matchesRegularExpression('/^[a-f0-9-]+\.ics$/'),
				$result,
				CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION
			);

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'identicalDataProvider')]
	public function testRunIdentical(string $uid, array $calendarObject, string $body, string $format, string $result): void {
		$refreshWebcalService = new RefreshWebcalService(
			$this->caldavBackend,
			$this->logger,
			$this->connection,
			$this->timeFactory,
			$this->importService
		);

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

		$stream = $this->createStreamFromString($body);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn(['data' => $stream, 'format' => $format]);

		$this->caldavBackend->expects(self::once())
			->method('getLimitedCalendarObjects')
			->willReturn($calendarObject);

		// Create a VCalendar object that will be yielded by the import service
		$vCalendar = VObject\Reader::read($result);

		$generator = function () use ($vCalendar) {
			yield $vCalendar;
		};

		$this->importService->expects(self::once())
			->method('importText')
			->willReturn($generator());

		$this->caldavBackend->expects(self::never())
			->method('createCalendarObject');

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	public function testSubscriptionNotFound(): void {
		$refreshWebcalService = new RefreshWebcalService(
			$this->caldavBackend,
			$this->logger,
			$this->connection,
			$this->timeFactory,
			$this->importService
		);

		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->willReturn([]);

		$this->connection->expects(self::never())
			->method('queryWebcalFeed');

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	public function testConnectionReturnsNull(): void {
		$refreshWebcalService = new RefreshWebcalService(
			$this->caldavBackend,
			$this->logger,
			$this->connection,
			$this->timeFactory,
			$this->importService
		);

		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->willReturn([
				[
					'id' => '42',
					'uri' => 'sub123',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla2',
					'lastmodified' => 0,
				],
			]);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn(null);

		$this->importService->expects(self::never())
			->method('importText');

		$this->caldavBackend->expects(self::never())
			->method('createCalendarObject');

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	public function testDeletedObjectsArePurged(): void {
		$refreshWebcalService = new RefreshWebcalService(
			$this->caldavBackend,
			$this->logger,
			$this->connection,
			$this->timeFactory,
			$this->importService
		);

		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->willReturn([
				[
					'id' => '42',
					'uri' => 'sub123',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla2',
					'lastmodified' => 0,
				],
			]);

		$body = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nUID:new-event\r\nDTSTAMP:20160218T133704Z\r\nDTSTART:20160218T133704Z\r\nSUMMARY:New Event\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
		$stream = $this->createStreamFromString($body);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn(['data' => $stream, 'format' => 'ical']);

		// Existing objects include one that won't be in the feed
		$this->caldavBackend->expects(self::once())
			->method('getLimitedCalendarObjects')
			->willReturn([
				'old-deleted-event' => [
					'id' => 99,
					'uid' => 'old-deleted-event',
					'etag' => 'old-etag',
					'uri' => 'old-event.ics',
				],
			]);

		$vCalendar = VObject\Reader::read($body);
		$generator = function () use ($vCalendar) {
			yield $vCalendar;
		};

		$this->importService->expects(self::once())
			->method('importText')
			->willReturn($generator());

		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject');

		$this->caldavBackend->expects(self::once())
			->method('purgeCachedEventsForSubscription')
			->with(42, [99], ['old-event.ics']);

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	public function testLongUidIsSkipped(): void {
		$refreshWebcalService = new RefreshWebcalService(
			$this->caldavBackend,
			$this->logger,
			$this->connection,
			$this->timeFactory,
			$this->importService
		);

		$this->caldavBackend->expects(self::once())
			->method('getSubscriptionsForUser')
			->with('principals/users/testuser')
			->willReturn([
				[
					'id' => '42',
					'uri' => 'sub123',
					RefreshWebcalService::STRIP_TODOS => '1',
					RefreshWebcalService::STRIP_ALARMS => '1',
					RefreshWebcalService::STRIP_ATTACHMENTS => '1',
					'source' => 'webcal://foo.bar/bla2',
					'lastmodified' => 0,
				],
			]);

		// Create a UID that is longer than 512 characters
		$longUid = str_repeat('a', 513);
		$body = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nUID:$longUid\r\nDTSTAMP:20160218T133704Z\r\nDTSTART:20160218T133704Z\r\nSUMMARY:Event with long UID\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
		$stream = $this->createStreamFromString($body);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn(['data' => $stream, 'format' => 'ical']);

		$this->caldavBackend->expects(self::once())
			->method('getLimitedCalendarObjects')
			->willReturn([]);

		$vCalendar = VObject\Reader::read($body);
		$generator = function () use ($vCalendar) {
			yield $vCalendar;
		};

		$this->importService->expects(self::once())
			->method('importText')
			->willReturn($generator());

		// Event with long UID should be skipped, so createCalendarObject should never be called
		$this->caldavBackend->expects(self::never())
			->method('createCalendarObject');

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'runDataProvider')]
	public function testRunCreateCalendarNoException(string $body, string $format, string $result): void {
		$refreshWebcalService = $this->getMockBuilder(RefreshWebcalService::class)
			->onlyMethods(['getSubscription'])
			->setConstructorArgs([$this->caldavBackend, $this->logger, $this->connection, $this->timeFactory, $this->importService])
			->getMock();

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

		$stream = $this->createStreamFromString($body);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn(['data' => $stream, 'format' => $format]);

		$this->caldavBackend->expects(self::once())
			->method('getLimitedCalendarObjects')
			->willReturn([]);

		// Create a VCalendar object that will be yielded by the import service
		$vCalendar = VObject\Reader::read($result);

		$generator = function () use ($vCalendar) {
			yield $vCalendar;
		};

		$this->importService->expects(self::once())
			->method('importText')
			->willReturn($generator());

		$noInstanceException = new NoInstancesException("can't add calendar object");
		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->willThrowException($noInstanceException);

		$this->logger->expects(self::once())
			->method('warning')
			->with('Unable to create calendar object from subscription {subscriptionId}', ['exception' => $noInstanceException, 'subscriptionId' => '42', 'source' => 'webcal://foo.bar/bla2']);

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'runDataProvider')]
	public function testRunCreateCalendarBadRequest(string $body, string $format, string $result): void {
		$refreshWebcalService = $this->getMockBuilder(RefreshWebcalService::class)
			->onlyMethods(['getSubscription'])
			->setConstructorArgs([$this->caldavBackend, $this->logger, $this->connection, $this->timeFactory, $this->importService])
			->getMock();

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

		$stream = $this->createStreamFromString($body);

		$this->connection->expects(self::once())
			->method('queryWebcalFeed')
			->willReturn(['data' => $stream, 'format' => $format]);

		$this->caldavBackend->expects(self::once())
			->method('getLimitedCalendarObjects')
			->willReturn([]);

		// Create a VCalendar object that will be yielded by the import service
		$vCalendar = VObject\Reader::read($result);

		$generator = function () use ($vCalendar) {
			yield $vCalendar;
		};

		$this->importService->expects(self::once())
			->method('importText')
			->willReturn($generator());

		$badRequestException = new BadRequest("can't add reach calendar url");
		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->willThrowException($badRequestException);

		$this->logger->expects(self::once())
			->method('warning')
			->with('Unable to create calendar object from subscription {subscriptionId}', ['exception' => $badRequestException, 'subscriptionId' => '42', 'source' => 'webcal://foo.bar/bla2']);

		$refreshWebcalService->refreshSubscription('principals/users/testuser', 'sub123');
	}

	public static function identicalDataProvider(): array {
		$icalBody = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
		$etag = md5($icalBody);

		return [
			[
				'12345',
				[
					'12345' => [
						'id' => 42,
						'etag' => $etag,
						'uri' => 'sub456.ics',
					],
				],
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 4.1.1//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				'ical',
				$icalBody,
			],
		];
	}

	public static function runDataProvider(): array {
		return [
			[
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject 4.1.1//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
				'ical',
				"BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabre//Sabre VObject " . VObject\Version::VERSION . "//EN\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:12345\r\nDTSTAMP:20160218T133704Z\r\nDTSTART;VALUE=DATE:19000101\r\nDTEND;VALUE=DATE:19000102\r\nRRULE:FREQ=YEARLY\r\nSUMMARY:12345's Birthday (1900)\r\nTRANSP:TRANSPARENT\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n",
			],
		];
	}
}
