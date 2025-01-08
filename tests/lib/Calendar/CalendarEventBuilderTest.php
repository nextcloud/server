<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Calendar;

use DateTimeImmutable;
use InvalidArgumentException;
use OC\Calendar\CalendarEventBuilder;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Calendar\ICreateFromString;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarEventBuilderTest extends TestCase {
	private CalendarEventBuilder $calendarEventBuilder;
	private ITimeFactory&MockObject $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('now')
			->willReturn(new DateTimeImmutable('20250105T000000Z'));

		$this->calendarEventBuilder = new CalendarEventBuilder(
			'event-uid-123',
			$this->timeFactory,
		);
	}

	public function testToIcs(): void {
		$this->calendarEventBuilder->setStartDate(new DateTimeImmutable('2025-01-05T17:09:58Z'));
		$this->calendarEventBuilder->setEndDate(new DateTimeImmutable('2025-01-05T17:19:58Z'));
		$this->calendarEventBuilder->setSummary('My event');
		$this->calendarEventBuilder->setDescription('Foo bar baz');
		$this->calendarEventBuilder->setOrganizer('mailto:organizer@domain.tld');
		$this->calendarEventBuilder->addAttendee('mailto:attendee1@domain.tld');
		$this->calendarEventBuilder->addAttendee('mailto:attendee2@domain.tld');

		$expected = file_get_contents(\OC::$SERVERROOT . '/tests/data/ics/event-builder-complete.ics');
		$actual = $this->calendarEventBuilder->toIcs();
		$this->assertEquals($expected, $actual);
	}

	public function testToIcsWithoutOrganizerAndAttendees(): void {
		$this->calendarEventBuilder->setStartDate(new DateTimeImmutable('2025-01-05T17:09:58Z'));
		$this->calendarEventBuilder->setEndDate(new DateTimeImmutable('2025-01-05T17:19:58Z'));
		$this->calendarEventBuilder->setSummary('My event');
		$this->calendarEventBuilder->setDescription('Foo bar baz');

		$expected = file_get_contents(\OC::$SERVERROOT . '/tests/data/ics/event-builder-without-attendees.ics');
		$actual = $this->calendarEventBuilder->toIcs();
		$this->assertEquals($expected, $actual);
	}

	public function testToIcsWithoutMailtoPrefix(): void {
		$this->calendarEventBuilder->setStartDate(new DateTimeImmutable('2025-01-05T17:09:58Z'));
		$this->calendarEventBuilder->setEndDate(new DateTimeImmutable('2025-01-05T17:19:58Z'));
		$this->calendarEventBuilder->setSummary('My event');
		$this->calendarEventBuilder->setDescription('Foo bar baz');
		$this->calendarEventBuilder->setOrganizer('organizer@domain.tld');
		$this->calendarEventBuilder->addAttendee('attendee1@domain.tld');
		$this->calendarEventBuilder->addAttendee('attendee2@domain.tld');

		$expected = file_get_contents(\OC::$SERVERROOT . '/tests/data/ics/event-builder-complete.ics');
		$actual = $this->calendarEventBuilder->toIcs();
		$this->assertEquals($expected, $actual);
	}

	public function testCreateInCalendar(): void {
		$this->calendarEventBuilder->setStartDate(new DateTimeImmutable('2025-01-05T17:09:58Z'));
		$this->calendarEventBuilder->setEndDate(new DateTimeImmutable('2025-01-05T17:19:58Z'));
		$this->calendarEventBuilder->setSummary('My event');
		$this->calendarEventBuilder->setDescription('Foo bar baz');
		$this->calendarEventBuilder->setOrganizer('organizer@domain.tld');
		$this->calendarEventBuilder->addAttendee('attendee1@domain.tld');
		$this->calendarEventBuilder->addAttendee('mailto:attendee2@domain.tld');

		$expectedIcs = file_get_contents(\OC::$SERVERROOT . '/tests/data/ics/event-builder-complete.ics');
		$calendar = $this->createMock(ICreateFromString::class);
		$calendar->expects(self::once())
			->method('createFromString')
			->with('event-uid-123.ics', $expectedIcs);

		$actual = $this->calendarEventBuilder->createInCalendar($calendar);
		$this->assertEquals('event-uid-123.ics', $actual);
	}

	public function testToIcsWithoutStartDate(): void {
		$this->calendarEventBuilder->setEndDate(new DateTimeImmutable('2025-01-05T17:19:58Z'));
		$this->calendarEventBuilder->setSummary('My event');
		$this->calendarEventBuilder->setDescription('Foo bar baz');
		$this->calendarEventBuilder->setOrganizer('organizer@domain.tld');
		$this->calendarEventBuilder->addAttendee('attendee1@domain.tld');
		$this->calendarEventBuilder->addAttendee('mailto:attendee2@domain.tld');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/start date/i');
		$this->calendarEventBuilder->toIcs();
	}

	public function testToIcsWithoutEndDate(): void {
		$this->calendarEventBuilder->setStartDate(new DateTimeImmutable('2025-01-05T17:09:58Z'));
		$this->calendarEventBuilder->setSummary('My event');
		$this->calendarEventBuilder->setDescription('Foo bar baz');
		$this->calendarEventBuilder->setOrganizer('organizer@domain.tld');
		$this->calendarEventBuilder->addAttendee('attendee1@domain.tld');
		$this->calendarEventBuilder->addAttendee('mailto:attendee2@domain.tld');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/end date/i');
		$this->calendarEventBuilder->toIcs();
	}

	public function testToIcsWithoutSummary(): void {
		$this->calendarEventBuilder->setStartDate(new DateTimeImmutable('2025-01-05T17:09:58Z'));
		$this->calendarEventBuilder->setEndDate(new DateTimeImmutable('2025-01-05T17:19:58Z'));
		$this->calendarEventBuilder->setDescription('Foo bar baz');
		$this->calendarEventBuilder->setOrganizer('organizer@domain.tld');
		$this->calendarEventBuilder->addAttendee('attendee1@domain.tld');
		$this->calendarEventBuilder->addAttendee('mailto:attendee2@domain.tld');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/summary/i');
		$this->calendarEventBuilder->toIcs();
	}

	public function testToIcsWithoutOrganizerWithAttendees(): void {
		$this->calendarEventBuilder->setStartDate(new DateTimeImmutable('2025-01-05T17:09:58Z'));
		$this->calendarEventBuilder->setEndDate(new DateTimeImmutable('2025-01-05T17:19:58Z'));
		$this->calendarEventBuilder->setSummary('My event');
		$this->calendarEventBuilder->setDescription('Foo bar baz');
		$this->calendarEventBuilder->addAttendee('attendee1@domain.tld');
		$this->calendarEventBuilder->addAttendee('mailto:attendee2@domain.tld');

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/organizer/i');
		$this->calendarEventBuilder->toIcs();
	}
}
