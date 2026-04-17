<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Search;

use OC\Search\Filter\DateTimeFilter;
use OC\Search\Filter\StringFilter;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Search\EventsSearchProvider;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IFilter;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Reader;
use Test\TestCase;

class EventsSearchProviderTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private IL10N&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;
	private CalDavBackend&MockObject $backend;
	private EventsSearchProvider $provider;

	// NO SUMMARY
	private static string $vEvent0 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Apple Inc.//Mac OS X 10.11.6//EN' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20161004T144433Z' . PHP_EOL
		. 'UID:85560E76-1B0D-47E1-A735-21625767FCA4' . PHP_EOL
		. 'DTEND;VALUE=DATE:20161008' . PHP_EOL
		. 'TRANSP:TRANSPARENT' . PHP_EOL
		. 'DTSTART;VALUE=DATE:20161005' . PHP_EOL
		. 'DTSTAMP:20161004T144437Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// TIMED SAME DAY
	private static string $vEvent1 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Tests//' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VTIMEZONE' . PHP_EOL
		. 'TZID:Europe/Berlin' . PHP_EOL
		. 'BEGIN:DAYLIGHT' . PHP_EOL
		. 'TZOFFSETFROM:+0100' . PHP_EOL
		. 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU' . PHP_EOL
		. 'DTSTART:19810329T020000' . PHP_EOL
		. 'TZNAME:GMT+2' . PHP_EOL
		. 'TZOFFSETTO:+0200' . PHP_EOL
		. 'END:DAYLIGHT' . PHP_EOL
		. 'BEGIN:STANDARD' . PHP_EOL
		. 'TZOFFSETFROM:+0200' . PHP_EOL
		. 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU' . PHP_EOL
		. 'DTSTART:19961027T030000' . PHP_EOL
		. 'TZNAME:GMT+1' . PHP_EOL
		. 'TZOFFSETTO:+0100' . PHP_EOL
		. 'END:STANDARD' . PHP_EOL
		. 'END:VTIMEZONE' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20160809T163629Z' . PHP_EOL
		. 'UID:0AD16F58-01B3-463B-A215-FD09FC729A02' . PHP_EOL
		. 'DTEND;TZID=Europe/Berlin:20160816T100000' . PHP_EOL
		. 'TRANSP:OPAQUE' . PHP_EOL
		. 'SUMMARY:Test Europe Berlin' . PHP_EOL
		. 'DTSTART;TZID=Europe/Berlin:20160816T090000' . PHP_EOL
		. 'DTSTAMP:20160809T163632Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// TIMED DIFFERENT DAY
	private static string $vEvent2 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Tests//' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VTIMEZONE' . PHP_EOL
		. 'TZID:Europe/Berlin' . PHP_EOL
		. 'BEGIN:DAYLIGHT' . PHP_EOL
		. 'TZOFFSETFROM:+0100' . PHP_EOL
		. 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU' . PHP_EOL
		. 'DTSTART:19810329T020000' . PHP_EOL
		. 'TZNAME:GMT+2' . PHP_EOL
		. 'TZOFFSETTO:+0200' . PHP_EOL
		. 'END:DAYLIGHT' . PHP_EOL
		. 'BEGIN:STANDARD' . PHP_EOL
		. 'TZOFFSETFROM:+0200' . PHP_EOL
		. 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU' . PHP_EOL
		. 'DTSTART:19961027T030000' . PHP_EOL
		. 'TZNAME:GMT+1' . PHP_EOL
		. 'TZOFFSETTO:+0100' . PHP_EOL
		. 'END:STANDARD' . PHP_EOL
		. 'END:VTIMEZONE' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20160809T163629Z' . PHP_EOL
		. 'UID:0AD16F58-01B3-463B-A215-FD09FC729A02' . PHP_EOL
		. 'DTEND;TZID=Europe/Berlin:20160817T100000' . PHP_EOL
		. 'TRANSP:OPAQUE' . PHP_EOL
		. 'SUMMARY:Test Europe Berlin' . PHP_EOL
		. 'DTSTART;TZID=Europe/Berlin:20160816T090000' . PHP_EOL
		. 'DTSTAMP:20160809T163632Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// ALL-DAY ONE-DAY
	private static string $vEvent3 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Apple Inc.//Mac OS X 10.11.6//EN' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20161004T144433Z' . PHP_EOL
		. 'UID:85560E76-1B0D-47E1-A735-21625767FCA4' . PHP_EOL
		. 'DTEND;VALUE=DATE:20161006' . PHP_EOL
		. 'TRANSP:TRANSPARENT' . PHP_EOL
		. 'DTSTART;VALUE=DATE:20161005' . PHP_EOL
		. 'DTSTAMP:20161004T144437Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// ALL-DAY MULTIPLE DAYS
	private static string $vEvent4 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Apple Inc.//Mac OS X 10.11.6//EN' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20161004T144433Z' . PHP_EOL
		. 'UID:85560E76-1B0D-47E1-A735-21625767FCA4' . PHP_EOL
		. 'DTEND;VALUE=DATE:20161008' . PHP_EOL
		. 'TRANSP:TRANSPARENT' . PHP_EOL
		. 'DTSTART;VALUE=DATE:20161005' . PHP_EOL
		. 'DTSTAMP:20161004T144437Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// DURATION
	private static string $vEvent5 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Apple Inc.//Mac OS X 10.11.6//EN' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20161004T144433Z' . PHP_EOL
		. 'UID:85560E76-1B0D-47E1-A735-21625767FCA4' . PHP_EOL
		. 'DURATION:P5D' . PHP_EOL
		. 'TRANSP:TRANSPARENT' . PHP_EOL
		. 'DTSTART;VALUE=DATE:20161005' . PHP_EOL
		. 'DTSTAMP:20161004T144437Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// NO DTEND - DATE
	private static string $vEvent6 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Apple Inc.//Mac OS X 10.11.6//EN' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20161004T144433Z' . PHP_EOL
		. 'UID:85560E76-1B0D-47E1-A735-21625767FCA4' . PHP_EOL
		. 'TRANSP:TRANSPARENT' . PHP_EOL
		. 'DTSTART;VALUE=DATE:20161005' . PHP_EOL
		. 'DTSTAMP:20161004T144437Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// NO DTEND - DATE-TIME
	private static string $vEvent7 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Tests//' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VTIMEZONE' . PHP_EOL
		. 'TZID:Europe/Berlin' . PHP_EOL
		. 'BEGIN:DAYLIGHT' . PHP_EOL
		. 'TZOFFSETFROM:+0100' . PHP_EOL
		. 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU' . PHP_EOL
		. 'DTSTART:19810329T020000' . PHP_EOL
		. 'TZNAME:GMT+2' . PHP_EOL
		. 'TZOFFSETTO:+0200' . PHP_EOL
		. 'END:DAYLIGHT' . PHP_EOL
		. 'BEGIN:STANDARD' . PHP_EOL
		. 'TZOFFSETFROM:+0200' . PHP_EOL
		. 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU' . PHP_EOL
		. 'DTSTART:19961027T030000' . PHP_EOL
		. 'TZNAME:GMT+1' . PHP_EOL
		. 'TZOFFSETTO:+0100' . PHP_EOL
		. 'END:STANDARD' . PHP_EOL
		. 'END:VTIMEZONE' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'CREATED:20160809T163629Z' . PHP_EOL
		. 'UID:0AD16F58-01B3-463B-A215-FD09FC729A02' . PHP_EOL
		. 'TRANSP:OPAQUE' . PHP_EOL
		. 'SUMMARY:Test Europe Berlin' . PHP_EOL
		. 'DTSTART;TZID=Europe/Berlin:20160816T090000' . PHP_EOL
		. 'DTSTAMP:20160809T163632Z' . PHP_EOL
		. 'SEQUENCE:0' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	// Stored in a non-UTC timezone on purpose: expand() rewrites occurrences to UTC,
	// so this exercises that the result is converted back to the event's local time.
	private static string $vEvent8 = 'BEGIN:VCALENDAR' . PHP_EOL
		. 'VERSION:2.0' . PHP_EOL
		. 'PRODID:-//Tests//' . PHP_EOL
		. 'CALSCALE:GREGORIAN' . PHP_EOL
		. 'BEGIN:VEVENT' . PHP_EOL
		. 'UID:recurring-yearly@example.com' . PHP_EOL
		. 'DTSTAMP:20240601T080000Z' . PHP_EOL
		. 'DTSTART;TZID=Europe/Berlin:20240601T090000' . PHP_EOL
		. 'DTEND;TZID=Europe/Berlin:20240601T100000' . PHP_EOL
		. 'RRULE:FREQ=YEARLY' . PHP_EOL
		. 'SUMMARY:Recurring yearly event' . PHP_EOL
		. 'END:VEVENT' . PHP_EOL
		. 'END:VCALENDAR';

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->backend = $this->createMock(CalDavBackend::class);

		$this->provider = new EventsSearchProvider(
			$this->appManager,
			$this->l10n,
			$this->urlGenerator,
			$this->backend
		);
	}

	public function testGetId(): void {
		$this->assertEquals('calendar', $this->provider->getId());
	}

	public function testGetName(): void {
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->with('Events')
			->willReturnArgument(0);

		$this->assertEquals('Events', $this->provider->getName());
	}

	public function testSearchAppDisabled(): void {
		$user = $this->createMock(IUser::class);
		$query = $this->createMock(ISearchQuery::class);
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('calendar', $user)
			->willReturn(false);
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->willReturnArgument(0);
		$this->backend->expects($this->never())
			->method('getCalendarsForUser');
		$this->backend->expects($this->never())
			->method('getSubscriptionsForUser');
		$this->backend->expects($this->never())
			->method('searchPrincipalUri');

		$actual = $this->provider->search($user, $query);
		$data = $actual->jsonSerialize();
		$this->assertInstanceOf(SearchResult::class, $actual);
		$this->assertEquals('Events', $data['name']);
		$this->assertEmpty($data['entries']);
		$this->assertFalse($data['isPaginated']);
		$this->assertNull($data['cursor']);
	}

	public function testSearch(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('john.doe');
		$query = $this->createMock(ISearchQuery::class);
		$seachTermFilter = $this->createMock(IFilter::class);
		$query->method('getFilter')->willReturnCallback(function ($name) use ($seachTermFilter) {
			return match ($name) {
				'term' => $seachTermFilter,
				default => null,
			};
		});
		$seachTermFilter->method('get')->willReturn('search term');
		$query->method('getLimit')->willReturn(5);
		$query->method('getCursor')->willReturn(20);
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('calendar', $user)
			->willReturn(true);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->backend->expects($this->once())
			->method('getCalendarsForUser')
			->with('principals/users/john.doe')
			->willReturn([
				[
					'id' => 99,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'calendar-uri-99',
				], [
					'id' => 123,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'calendar-uri-123',
				]
			]);
		$this->backend->expects($this->once())
			->method('getSubscriptionsForUser')
			->with('principals/users/john.doe')
			->willReturn([
				[
					'id' => 1337,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'subscription-uri-1337',
				]
			]);
		$this->backend->expects($this->once())
			->method('searchPrincipalUri')
			->with('principals/users/john.doe', 'search term', ['VEVENT'],
				['SUMMARY', 'LOCATION', 'DESCRIPTION', 'ATTENDEE', 'ORGANIZER', 'CATEGORIES'],
				['ATTENDEE' => ['CN'], 'ORGANIZER' => ['CN']],
				['limit' => 5, 'offset' => 20, 'timerange' => ['start' => null, 'end' => null]])
			->willReturn([
				[
					'calendarid' => 99,
					'calendartype' => CalDavBackend::CALENDAR_TYPE_CALENDAR,
					'uri' => 'event0.ics',
					'calendardata' => self::$vEvent0,
				],
				[
					'calendarid' => 123,
					'calendartype' => CalDavBackend::CALENDAR_TYPE_CALENDAR,
					'uri' => 'event1.ics',
					'calendardata' => self::$vEvent1,
				],
				[
					'calendarid' => 1337,
					'calendartype' => CalDavBackend::CALENDAR_TYPE_SUBSCRIPTION,
					'uri' => 'event2.ics',
					'calendardata' => self::$vEvent2,
				]
			]);

		$provider = $this->getMockBuilder(EventsSearchProvider::class)
			->setConstructorArgs([
				$this->appManager,
				$this->l10n,
				$this->urlGenerator,
				$this->backend,
			])
			->onlyMethods([
				'getDeepLinkToCalendarApp',
				'generateSubline',
			])
			->getMock();

		$provider->expects($this->exactly(3))
			->method('generateSubline')
			->willReturn('subline');
		$provider->expects($this->exactly(3))
			->method('getDeepLinkToCalendarApp')
			->willReturnMap([
				['principals/users/john.doe', 'calendar-uri-99', 'event0.ics', 'deep-link-to-calendar'],
				['principals/users/john.doe', 'calendar-uri-123', 'event1.ics', 'deep-link-to-calendar'],
				['principals/users/john.doe', 'subscription-uri-1337', 'event2.ics', 'deep-link-to-calendar']
			]);

		$actual = $provider->search($user, $query);
		$data = $actual->jsonSerialize();
		$this->assertInstanceOf(SearchResult::class, $actual);
		$this->assertEquals('Events', $data['name']);
		$this->assertCount(3, $data['entries']);
		$this->assertTrue($data['isPaginated']);
		$this->assertEquals(23, $data['cursor']);

		$result0 = $data['entries'][0];
		$result0Data = $result0->jsonSerialize();
		$result1 = $data['entries'][1];
		$result1Data = $result1->jsonSerialize();
		$result2 = $data['entries'][2];
		$result2Data = $result2->jsonSerialize();

		$this->assertInstanceOf(SearchResultEntry::class, $result0);
		$this->assertEmpty($result0Data['thumbnailUrl']);
		$this->assertEquals('Untitled event', $result0Data['title']);
		$this->assertEquals('subline', $result0Data['subline']);
		$this->assertEquals('deep-link-to-calendar', $result0Data['resourceUrl']);
		$this->assertEquals('icon-calendar-dark', $result0Data['icon']);
		$this->assertFalse($result0Data['rounded']);

		$this->assertInstanceOf(SearchResultEntry::class, $result1);
		$this->assertEmpty($result1Data['thumbnailUrl']);
		$this->assertEquals('Test Europe Berlin', $result1Data['title']);
		$this->assertEquals('subline', $result1Data['subline']);
		$this->assertEquals('deep-link-to-calendar', $result1Data['resourceUrl']);
		$this->assertEquals('icon-calendar-dark', $result1Data['icon']);
		$this->assertFalse($result1Data['rounded']);

		$this->assertInstanceOf(SearchResultEntry::class, $result2);
		$this->assertEmpty($result2Data['thumbnailUrl']);
		$this->assertEquals('Test Europe Berlin', $result2Data['title']);
		$this->assertEquals('subline', $result2Data['subline']);
		$this->assertEquals('deep-link-to-calendar', $result2Data['resourceUrl']);
		$this->assertEquals('icon-calendar-dark', $result2Data['icon']);
		$this->assertFalse($result2Data['rounded']);
	}

	public static function provideDeepLinkData(): array {
		return [
			['principals/users/john.doe', 'bGluay10by1yZW1vdGUucGhwL2Rhdi9jYWxlbmRhcnMvam9obi5kb2UvZm9vL2Jhci5pY3M='],
			['principals/users/John Doe', 'bGluay10by1yZW1vdGUucGhwL2Rhdi9jYWxlbmRhcnMvSm9obiUyMERvZS9mb28vYmFyLmljcw=='],
			['principals/users/john@doe', 'bGluay10by1yZW1vdGUucGhwL2Rhdi9jYWxlbmRhcnMvam9obkBkb2UvZm9vL2Jhci5pY3M='],
		];
	}

	#[DataProvider(methodName: 'provideDeepLinkData')]
	public function testGetDeepLinkToCalendarApp(
		string $principalUri,
		string $expectedBase64DavUrl,
	): void {
		$this->urlGenerator->expects($this->once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('link-to-remote.php');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('calendar.view.index')
			->willReturn('link-to-route-calendar/');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with("link-to-route-calendar/edit/$expectedBase64DavUrl")
			->willReturn('absolute-url-to-route');

		$actual = self::invokePrivate($this->provider, 'getDeepLinkToCalendarApp', [
			$principalUri,
			'foo',
			'bar.ics',
		]);

		$this->assertEquals('absolute-url-to-route', $actual);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'generateSublineDataProvider')]
	public function testGenerateSubline(string $ics, string $expectedSubline, array $calendarInfo = []): void {
		$vCalendar = Reader::read($ics, Reader::OPTION_FORGIVING);
		$eventComponent = $vCalendar->VEVENT;

		$this->l10n->method('l')
			->willReturnCallback(static function (string $type, \DateTime $date, $_):string {
				if ($type === 'time') {
					return $date->format('H:i');
				}

				return $date->format('m-d');
			});

		$actual = self::invokePrivate($this->provider, 'generateSubline', [$eventComponent, $calendarInfo]);
		$this->assertEquals($expectedSubline, $actual);
	}

	public static function generateSublineDataProvider(): array {
		return [
			[self::$vEvent1, '08-16 09:00 - 10:00', []],
			[self::$vEvent2, '08-16 09:00 - 08-17 10:00', []],
			[self::$vEvent3, '10-05', []],
			[self::$vEvent4, '10-05 - 10-07', []],
			[self::$vEvent5, '10-05 - 10-09', []],
			[self::$vEvent6, '10-05', []],
			[self::$vEvent7, '08-16 09:00 - 09:00', []],
			[self::$vEvent1, '08-16 09:00 - 10:00 (My Calendar)', ['{DAV:}displayname' => 'My Calendar']],
			[self::$vEvent3, '10-05 (My Calendar)', ['{DAV:}displayname' => 'My Calendar']],
			[self::$vEvent2, '08-16 09:00 - 08-17 10:00 (My Calendar)', ['{DAV:}displayname' => 'My Calendar']],
			[self::$vEvent1, '08-16 09:00 - 10:00', ['{DAV:}displayname' => '']],
		];
	}

	public function testGetPrimaryComponentReturnsTheOnlyComponent(): void {
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:single-1
DTSTART;TZID=Europe/Berlin:20240601T090000
SUMMARY:Only
END:VEVENT
END:VCALENDAR
ICS;
		$document = Reader::read($ics, Reader::OPTION_FORGIVING);

		$actual = self::invokePrivate($this->provider, 'getPrimaryComponent', [$document, 'VEVENT']);

		$this->assertSame('Only', (string)$actual->SUMMARY);
	}

	public function testGetPrimaryComponentReturnsTheRecurrenceSetMaster(): void {
		// The override is intentionally listed before the master to prove the
		// selection is driven by the missing RECURRENCE-ID, not document order.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:recur-1
RECURRENCE-ID;TZID=Europe/Berlin:20240602T090000
DTSTART;TZID=Europe/Berlin:20240602T090000
SUMMARY:Override
END:VEVENT
BEGIN:VEVENT
UID:recur-1
RRULE:FREQ=DAILY
DTSTART;TZID=Europe/Berlin:20240601T090000
SUMMARY:Master
END:VEVENT
END:VCALENDAR
ICS;
		$document = Reader::read($ics, Reader::OPTION_FORGIVING);

		$actual = self::invokePrivate($this->provider, 'getPrimaryComponent', [$document, 'VEVENT']);

		$this->assertSame('Master', (string)$actual->SUMMARY);
	}

	public function testGetPrimaryComponentFallsBackToFirstWhenAllAreOverrides(): void {
		// Expanded occurrences all carry a RECURRENCE-ID, so the fallback returns
		// the first element of the set.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:recur-1
RECURRENCE-ID;TZID=Europe/Berlin:20240601T090000
DTSTART;TZID=Europe/Berlin:20240601T090000
SUMMARY:First
END:VEVENT
BEGIN:VEVENT
UID:recur-1
RECURRENCE-ID;TZID=Europe/Berlin:20240602T090000
DTSTART;TZID=Europe/Berlin:20240602T090000
SUMMARY:Second
END:VEVENT
END:VCALENDAR
ICS;
		$document = Reader::read($ics, Reader::OPTION_FORGIVING);

		$actual = self::invokePrivate($this->provider, 'getPrimaryComponent', [$document, 'VEVENT']);

		$this->assertSame('First', (string)$actual->SUMMARY);
	}

	public function testGetPrimaryComponentReturnsNullWhenComponentTypeIsAbsent(): void {
		// A calendar without the requested component type must not crash.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VTODO
UID:todo-1
SUMMARY:A task
END:VTODO
END:VCALENDAR
ICS;
		$document = Reader::read($ics, Reader::OPTION_FORGIVING);

		$actual = self::invokePrivate($this->provider, 'getPrimaryComponent', [$document, 'VEVENT']);

		$this->assertNull($actual);
	}

	public function testExpandInRangeReturnsNullWhenOutOfRange(): void {
		// A recurring event whose occurrences all fall outside the requested
		// window: expand() yields nothing, so the caller falls back to the master.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:recur-1
DTSTART;TZID=Europe/Berlin:20240601T090000
DTEND;TZID=Europe/Berlin:20240601T100000
RRULE:FREQ=DAILY;COUNT=3
SUMMARY:Daily standup
END:VEVENT
END:VCALENDAR
ICS;
		$vCalendar = Reader::read($ics, Reader::OPTION_FORGIVING);
		$since = new \DateTimeImmutable('2000-01-01T00:00:00Z');
		$until = new \DateTimeImmutable('2000-01-02T00:00:00Z');

		$actual = self::invokePrivate($this->provider, 'expandInRange', [$vCalendar, $since, $until]);

		$this->assertNull($actual);
	}

	public function testExpandInRangeReturnsOccurrenceInOriginalTimeZone(): void {
		// The in-range occurrence is converted back from the UTC that expand()
		// forces to the event's original timezone.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:recur-1
DTSTART;TZID=Europe/Berlin:20240601T090000
DTEND;TZID=Europe/Berlin:20240601T100000
RRULE:FREQ=DAILY;COUNT=3
SUMMARY:Daily standup
END:VEVENT
END:VCALENDAR
ICS;
		$vCalendar = Reader::read($ics, Reader::OPTION_FORGIVING);
		$since = new \DateTimeImmutable('2024-06-02T00:00:00Z');
		$until = new \DateTimeImmutable('2024-06-03T00:00:00Z');

		$expanded = self::invokePrivate($this->provider, 'expandInRange', [$vCalendar, $since, $until]);
		$occurrence = self::invokePrivate($this->provider, 'getPrimaryComponent', [$expanded, 'VEVENT']);

		$this->assertSame('Europe/Berlin', $occurrence->DTSTART->getDateTime()->getTimezone()->getName());
		$this->assertSame('2024-06-02 09:00', $occurrence->DTSTART->getDateTime()->format('Y-m-d H:i'));
	}

	public function testResolveComponentReturnsNullForNonCalendar(): void {
		// The backend hands us opaque data; a non-calendar object is dropped.
		$ics = <<<ICS
BEGIN:VCARD
VERSION:4.0
UID:card-1
FN:John Doe
END:VCARD
ICS;
		$since = new \DateTimeImmutable('2024-06-02T00:00:00Z');
		$until = new \DateTimeImmutable('2024-06-03T00:00:00Z');

		$actual = self::invokePrivate($this->provider, 'resolveComponent', [$ics, $since, $until]);

		$this->assertNull($actual);
	}

	public function testResolveComponentReturnsNullWhenOutOfRange(): void {
		// No occurrence within the requested window: the row is dropped.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:recur-1
DTSTART;TZID=Europe/Berlin:20240601T090000
DTEND;TZID=Europe/Berlin:20240601T100000
RRULE:FREQ=DAILY;COUNT=3
SUMMARY:Daily standup
END:VEVENT
END:VCALENDAR
ICS;
		$since = new \DateTimeImmutable('2000-01-01T00:00:00Z');
		$until = new \DateTimeImmutable('2000-01-02T00:00:00Z');

		$actual = self::invokePrivate($this->provider, 'resolveComponent', [$ics, $since, $until]);

		$this->assertNull($actual);
	}

	public function testResolveComponentReturnsInRangeOccurrence(): void {
		// With a time range, the in-range occurrence is returned in local time.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:recur-1
DTSTART;TZID=Europe/Berlin:20240601T090000
DTEND;TZID=Europe/Berlin:20240601T100000
RRULE:FREQ=DAILY;COUNT=3
SUMMARY:Daily standup
END:VEVENT
END:VCALENDAR
ICS;
		$since = new \DateTimeImmutable('2024-06-02T00:00:00Z');
		$until = new \DateTimeImmutable('2024-06-03T00:00:00Z');

		$actual = self::invokePrivate($this->provider, 'resolveComponent', [$ics, $since, $until]);

		$this->assertSame('Europe/Berlin', $actual->DTSTART->getDateTime()->getTimezone()->getName());
		$this->assertSame('2024-06-02 09:00', $actual->DTSTART->getDateTime()->format('Y-m-d H:i'));
	}

	public function testResolveComponentReturnsMasterWithoutTimeRange(): void {
		// Without a time range nothing is expanded; the master is kept.
		$ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Tests//
BEGIN:VEVENT
UID:recur-1
DTSTART;TZID=Europe/Berlin:20240601T090000
DTEND;TZID=Europe/Berlin:20240601T100000
RRULE:FREQ=DAILY;COUNT=3
SUMMARY:Daily standup
END:VEVENT
END:VCALENDAR
ICS;

		$actual = self::invokePrivate($this->provider, 'resolveComponent', [$ics, null, null]);

		$this->assertSame('Daily standup', (string)$actual->SUMMARY);
		$this->assertSame('2024-06-01 09:00', $actual->DTSTART->getDateTime()->format('Y-m-d H:i'));
	}

	public function testSearchSince(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('john.doe');
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getFilter')->willReturnCallback(function ($name) {
			return match ($name) {
				'term' => new StringFilter('search term'),
				'since' => new DateTimeFilter('2026-05-15'),
				'until' => new DateTimeFilter('2026-06-14'),
				default => null,
			};
		});
		$query->method('getLimit')->willReturn(5);
		$query->method('getCursor')->willReturn(20);
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with('calendar', $user)
			->willReturn(true);
		$this->l10n->method('t')->willReturnArgument(0);
		$this->l10n->method('l')
			->willReturnCallback(static function (string $type, \DateTime $date, $_): string {
				if ($type === 'time') {
					return $date->format('H:i');
				}
				return $date->format('m-d');
			});
		$this->backend->expects($this->once())
			->method('getCalendarsForUser')
			->with('principals/users/john.doe')
			->willReturn([
				[
					'id' => 99,
					'principaluri' => 'principals/users/john.doe',
					'uri' => 'calendar-uri-99',
					'{DAV:}displayname' => 'My Calendar',
				]
			]);
		$this->backend->expects($this->once())
			->method('getSubscriptionsForUser')
			->with('principals/users/john.doe')
			->willReturn([]);
		$this->backend->expects($this->once())
			->method('searchPrincipalUri')
			->with('principals/users/john.doe', 'search term', ['VEVENT'],
				['SUMMARY', 'LOCATION', 'DESCRIPTION', 'ATTENDEE', 'ORGANIZER', 'CATEGORIES'],
				['ATTENDEE' => ['CN'], 'ORGANIZER' => ['CN']],
				['limit' => 5, 'offset' => 20, 'timerange' => ['start' => new \DateTimeImmutable('2026-05-15 00:00:00'), 'end' => new \DateTimeImmutable('2026-06-14 00:00:00')]])
			->willReturn([
				[
					'calendarid' => 99,
					'calendartype' => CalDavBackend::CALENDAR_TYPE_CALENDAR,
					'uri' => 'recurring-yearly-event.ics',
					'calendardata' => self::$vEvent8,
				]
			]);
		$this->urlGenerator->expects($this->once())
			->method('linkTo')
			->with('', 'remote.php')
			->willReturn('link-to-remote.php');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('calendar.view.index')
			->willReturn('link-to-route-calendar/');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('link-to-route-calendar/edit/bGluay10by1yZW1vdGUucGhwL2Rhdi9jYWxlbmRhcnMvam9obi5kb2UvY2FsZW5kYXItdXJpLTk5L3JlY3VycmluZy15ZWFybHktZXZlbnQuaWNz')
			->willReturn('deep-link-to-calendar');

		$actual = $this->provider->search($user, $query);

		$data = $actual->jsonSerialize();
		$this->assertInstanceOf(SearchResult::class, $actual);
		$this->assertEquals('Events', $data['name']);
		$this->assertCount(1, $data['entries']);
		$this->assertTrue($data['isPaginated']);
		$this->assertEquals(21, $data['cursor']);
		$result0 = $data['entries'][0];
		$result0Data = $result0->jsonSerialize();
		$this->assertInstanceOf(SearchResultEntry::class, $result0);
		$this->assertEmpty($result0Data['thumbnailUrl']);
		$this->assertEquals('Recurring yearly event', $result0Data['title']);
		// The occurrence is shown in the event's local time (Europe/Berlin, 09:00),
		// not in the UTC time that expand() produces (07:00).
		$this->assertEquals('06-01 09:00 - 10:00 (My Calendar)', $result0Data['subline']);
		$this->assertEquals('deep-link-to-calendar', $result0Data['resourceUrl']);
		$this->assertEquals('icon-calendar-dark', $result0Data['icon']);
		$this->assertFalse($result0Data['rounded']);
		$this->assertEquals('1780297200', $result0Data['attributes']['createdAt']);
	}
}
