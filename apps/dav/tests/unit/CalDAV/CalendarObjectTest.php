<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarObject;
use OCP\IL10N;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader as VObjectReader;
use Test\TestCase;

class CalendarObjectTest extends TestCase {
	private readonly CalDavBackend&MockObject $calDavBackend;
	private readonly IL10N&MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->calDavBackend = $this->createMock(CalDavBackend::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->l10n->method('t')
			->willReturnArgument(0);
	}

	public static function provideConfidentialObjectData(): array {
		return [
			// Shared writable
			[
				false,
				[
					'principaluri' => 'user1',
					'{http://owncloud.org/ns}owner-principal' => 'user2',
				],
			],
			[
				false,
				[
					'principaluri' => 'user1',
					'{http://owncloud.org/ns}owner-principal' => 'user2',
					'{http://owncloud.org/ns}read-only' => 0,
				],
			],
			[
				false,
				[
					'principaluri' => 'user1',
					'{http://owncloud.org/ns}owner-principal' => 'user2',
					'{http://owncloud.org/ns}read-only' => false,
				],
			],
			// Shared read-only
			[
				true,
				[
					'principaluri' => 'user1',
					'{http://owncloud.org/ns}owner-principal' => 'user2',
					'{http://owncloud.org/ns}read-only' => 1,
				],
			],
			[
				true,
				[
					'principaluri' => 'user1',
					'{http://owncloud.org/ns}owner-principal' => 'user2',
					'{http://owncloud.org/ns}read-only' => true,
				],
			],
		];
	}

	#[DataProvider(methodName: 'provideConfidentialObjectData')]
	public function testGetWithConfidentialObject(
		bool $expectConfidential,
		array $calendarInfo,
	): void {
		$ics = <<<EOF
BEGIN:VCALENDAR
CALSCALE:GREGORIAN
VERSION:2.0
PRODID:-//IDN nextcloud.com//Calendar app 5.5.0-dev.1//EN
BEGIN:VEVENT
CREATED:20250820T102647Z
DTSTAMP:20250820T103038Z
LAST-MODIFIED:20250820T103038Z
SEQUENCE:4
UID:a0f55f1f-4f0e-4db8-a54b-1e8b53846591
DTSTART;TZID=Europe/Berlin:20250822T110000
DTEND;TZID=Europe/Berlin:20250822T170000
STATUS:CONFIRMED
SUMMARY:confidential-event
CLASS:CONFIDENTIAL
LOCATION:A location
DESCRIPTION:A description
END:VEVENT
END:VCALENDAR
EOF;
		VObjectReader::read($ics);

		$calendarObject = new CalendarObject(
			$this->calDavBackend,
			$this->l10n,
			$calendarInfo,
			[
				'uri' => 'a0f55f1f-4f0e-4db8-a54b-1e8b53846591.ics',
				'calendardata' => $ics,
				'classification' => 2, // CalDavBackend::CLASSIFICATION_CONFIDENTIAL
			],
		);

		$actualIcs = $calendarObject->get();
		$vObject = VObjectReader::read($actualIcs);

		$this->assertInstanceOf(VCalendar::class, $vObject);
		$vEvent = $vObject->getBaseComponent('VEVENT');
		$this->assertInstanceOf(VEvent::class, $vEvent);

		if ($expectConfidential) {
			$this->assertEquals('Busy', $vEvent->SUMMARY?->getValue());
			$this->assertNull($vEvent->DESCRIPTION);
			$this->assertNull($vEvent->LOCATION);
		} else {
			$this->assertEquals('confidential-event', $vEvent->SUMMARY?->getValue());
			$this->assertNotNull($vEvent->DESCRIPTION);
			$this->assertNotNull($vEvent->LOCATION);
		}
	}
}
