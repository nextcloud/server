<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\ThunderbirdPutInvitationQuirkPlugin;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

class ThunderbirdPutInvitationQuirkPluginTest extends TestCase {
	private readonly ThunderbirdPutInvitationQuirkPlugin $plugin;

	private readonly Server&MockObject $server;
	private readonly IDBConnection&MockObject $db;

	protected function setUp(): void {
		parent::setUp();

		$this->server = $this->createMock(Server::class);
		$this->db = $this->createMock(IDBConnection::class);

		$this->plugin = new ThunderbirdPutInvitationQuirkPlugin(
			$this->db,
		);
	}

	public function testInitialize(): void {
		$this->server->expects(self::once())
			->method('on')
			->with('beforeMethod:PUT', $this->plugin->beforePut(...), 21);

		$this->plugin->initialize($this->server);
	}

	public static function provideBeforePutData(): array {
		return [
			// No collision
			[[], false],
			// Many collisions
			[
				[
					['uri' => 'sabredav-3dd349f8-58e0-483d-921f-70bc9f02366b.ics'],
					['uri' => 'sabredav-19a50615-2db0-4046-a537-000979925e16.ics'],
				],
				false,
			],
			// Exactly one collision
			[
				[
					['uri' => 'sabredav-ab2dd681-c265-4b1e-8a20-e9d356f2c33c.ics'],
				],
				true,
			],
		];
	}

	#[DataProvider('provideBeforePutData')]
	public function testBeforePut(array $rows, bool $expectUrlChange): void {
		$ics = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.6//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20250817T094141Z
LAST-MODIFIED:20250817T094211Z
SEQUENCE:2
UID:cc5d41aa-7dbc-4278-8ffd-4fb5d626397c
DTSTART;TZID=Europe/Berlin:20250819T030000
DTEND;TZID=Europe/Berlin:20250819T080000
STATUS:CONFIRMED
SUMMARY:thunderbird-put-test
ATTENDEE;CN=user a;CUTYPE=INDIVIDUAL;PARTSTAT=NEEDS-ACTION;ROLE=REQ-PARTICI
 PANT;RSVP=TRUE;LANGUAGE=en:mailto:usera@imap.localhost
ORGANIZER;CN=Admin Account:mailto:admin@imap.localhost
DTSTAMP:20250817T094211Z
END:VEVENT
END:VCALENDAR
EOF;

		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::exactly(2))
			->method('getHeader')
			->willReturnMap([
				['User-Agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Thunderbird/38.2.0 Lightning/4.0.2'],
				['Content-Type', 'text/calendar; charset=utf-8'],
			]);
		$request->expects(self::once())
			->method('getPath')
			->willReturn('calendars/usera/personal/cc5d41aa-7dbc-4278-8ffd-4fb5d626397c.ics');
		$request->expects(self::once())
			->method('getBodyAsString')
			->willReturn($ics);
		$request->expects(self::once())
			->method('setBody')
			->with($ics);
		if ($expectUrlChange) {
			$request->expects(self::once())
				->method('getUrl')
				->willReturn('remote.php/dav/calendars/usera/personal/cc5d41aa-7dbc-4278-8ffd-4fb5d626397c.ics');
			$request->expects(self::once())
				->method('setUrl')
				->with('remote.php/dav/calendars/usera/personal/sabredav-ab2dd681-c265-4b1e-8a20-e9d356f2c33c.ics');
		} else {
			$request->expects(self::never())
				->method('getUrl');
			$request->expects(self::never())
				->method('setUrl');
		}

		$authPlugin = $this->createMock(\Sabre\DAV\Auth\Plugin::class);
		$authPlugin->expects(self::once())
			->method('getCurrentPrincipal')
			->willReturn('principals/users/usera');
		$this->server->expects(self::once())
			->method('getPlugin')
			->with('auth')
			->willReturn($authPlugin);

		$qb = $this->createMock(IQueryBuilder::class);
		$qb->method('select')
			->willReturnSelf();
		$qb->method('from')
			->willReturnSelf();
		$qb->method('join')
			->willReturnSelf();
		$qb->method('where')
			->willReturnSelf();
		$expr = $this->createMock(IExpressionBuilder::class);
		$qb->method('expr')
			->willReturn($expr);
		$this->db->expects(self::once())
			->method('getQueryBuilder')
			->willReturn($qb);

		$result = $this->createMock(IResult::class);
		$result->expects(self::once())
			->method('fetchAll')
			->willReturn($rows);
		$result->expects(self::once())
			->method('closeCursor');
		$qb->expects(self::once())
			->method('executeQuery')
			->willReturn($result);

		$response = $this->createMock(ResponseInterface::class);

		$this->plugin->initialize($this->server);
		$this->plugin->beforePut($request, $response);
	}

	public function testBeforePutWithInvalidUserAgent(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::once())
			->method('getHeader')
			->with('User-Agent')
			->willReturn('curl/8.14.1');

		$request->expects(self::never())
			->method('setUrl');
		$this->db->expects(self::never())
			->method('getQueryBuilder');

		$response = $this->createMock(ResponseInterface::class);

		$this->plugin->initialize($this->server);
		$this->plugin->beforePut($request, $response);
	}

	public function testBeforePutWithUnrelatedRequestPath(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::once())
			->method('getHeader')
			->with('User-Agent')
			->willReturn('Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Thunderbird/38.2.0 Lightning/4.0.2');
		$request->expects(self::once())
			->method('getPath')
			->willReturn('foo/bar/baz');

		$request->expects(self::never())
			->method('setUrl');
		$this->db->expects(self::never())
			->method('getQueryBuilder');

		$response = $this->createMock(ResponseInterface::class);

		$this->plugin->initialize($this->server);
		$this->plugin->beforePut($request, $response);
	}

	public function testBeforePutWithInvalidContentType(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::exactly(2))
			->method('getHeader')
			->willReturnMap([
				['User-Agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Thunderbird/38.2.0 Lightning/4.0.2'],
				['Content-Type', 'foo/bar; charset=utf-8'],
			]);
		$request->expects(self::once())
			->method('getPath')
			->willReturn('calendars/usera/personal/cc5d41aa-7dbc-4278-8ffd-4fb5d626397c.ics');

		$request->expects(self::never())
			->method('setUrl');
		$this->db->expects(self::never())
			->method('getQueryBuilder');

		$response = $this->createMock(ResponseInterface::class);

		$this->plugin->initialize($this->server);
		$this->plugin->beforePut($request, $response);
	}

	public function testBeforePutWithoutCurrentUserPrincipal(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::exactly(2))
			->method('getHeader')
			->willReturnMap([
				['User-Agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Thunderbird/38.2.0 Lightning/4.0.2'],
				['Content-Type', 'text/calendar; charset=utf-8'],
			]);
		$request->expects(self::once())
			->method('getPath')
			->willReturn('calendars/usera/personal/cc5d41aa-7dbc-4278-8ffd-4fb5d626397c.ics');

		$authPlugin = $this->createMock(\Sabre\DAV\Auth\Plugin::class);
		$authPlugin->expects(self::once())
			->method('getCurrentPrincipal')
			->willReturn(null);
		$this->server->expects(self::once())
			->method('getPlugin')
			->with('auth')
			->willReturn($authPlugin);

		$request->expects(self::never())
			->method('setUrl');
		$this->db->expects(self::never())
			->method('getQueryBuilder');

		$response = $this->createMock(ResponseInterface::class);

		$this->plugin->initialize($this->server);
		$this->plugin->beforePut($request, $response);
	}

	public function testBeforePutWithoutAuthPlugin(): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::exactly(2))
			->method('getHeader')
			->willReturnMap([
				['User-Agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Thunderbird/38.2.0 Lightning/4.0.2'],
				['Content-Type', 'text/calendar; charset=utf-8'],
			]);
		$request->expects(self::once())
			->method('getPath')
			->willReturn('calendars/usera/personal/cc5d41aa-7dbc-4278-8ffd-4fb5d626397c.ics');

		$this->server->expects(self::once())
			->method('getPlugin')
			->with('auth')
			->willReturn(null);

		$request->expects(self::never())
			->method('setUrl');
		$this->db->expects(self::never())
			->method('getQueryBuilder');

		$response = $this->createMock(ResponseInterface::class);

		$this->plugin->initialize($this->server);
		$this->plugin->beforePut($request, $response);
	}

	public static function provideInvalidIcsData(): array {
		$noUid = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.6//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20250817T094141Z
LAST-MODIFIED:20250817T094211Z
SEQUENCE:2
DTSTART;TZID=Europe/Berlin:20250819T030000
DTEND;TZID=Europe/Berlin:20250819T080000
STATUS:CONFIRMED
SUMMARY:thunderbird-put-test
ATTENDEE;CN=user a;CUTYPE=INDIVIDUAL;PARTSTAT=NEEDS-ACTION;ROLE=REQ-PARTICI
 PANT;RSVP=TRUE;LANGUAGE=en:mailto:usera@imap.localhost
ORGANIZER;CN=Admin Account:mailto:admin@imap.localhost
DTSTAMP:20250817T094211Z
END:VEVENT
END:VCALENDAR
EOF;

	$noVEvent = <<<EOF
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 4.5.6//EN
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
END:VCALENDAR
EOF;

	$noVCalendar = <<<EOF
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
EOF;

		return [
			[$noUid],
			[$noVEvent],
			[$noVCalendar],
		];
	}

	#[DataProvider('provideInvalidIcsData')]
	public function testBeforePutWithInvalidIcs(string $ics): void {
		$request = $this->createMock(RequestInterface::class);
		$request->expects(self::exactly(2))
			->method('getHeader')
			->willReturnMap([
				['User-Agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:38.0) Gecko/20100101 Thunderbird/38.2.0 Lightning/4.0.2'],
				['Content-Type', 'text/calendar; charset=utf-8'],
			]);
		$request->expects(self::once())
			->method('getPath')
			->willReturn('calendars/usera/personal/cc5d41aa-7dbc-4278-8ffd-4fb5d626397c.ics');
		$request->expects(self::once())
			->method('getBodyAsString')
			->willReturn($ics);
		$request->expects(self::once())
			->method('setBody')
			->with($ics);

		$authPlugin = $this->createMock(\Sabre\DAV\Auth\Plugin::class);
		$authPlugin->expects(self::once())
			->method('getCurrentPrincipal')
			->willReturn('principals/users/usera');
		$this->server->expects(self::once())
			->method('getPlugin')
			->with('auth')
			->willReturn($authPlugin);

		$request->expects(self::never())
			->method('setUrl');
		$this->db->expects(self::never())
			->method('getQueryBuilder');

		$response = $this->createMock(ResponseInterface::class);

		$this->plugin->initialize($this->server);
		$this->plugin->beforePut($request, $response);
	}
}
