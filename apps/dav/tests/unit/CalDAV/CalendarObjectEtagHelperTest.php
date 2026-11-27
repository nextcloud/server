<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalendarObjectEtagHelper;
use Sabre\VObject\Reader;
use Test\TestCase;

class CalendarObjectEtagHelperTest extends TestCase {
	private const CALENDAR_DATA = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nUID:etag-test\r\nDTSTAMP:20260101T080000Z\r\nDTSTART:20260301T100000Z\r\nSUMMARY:Test Event\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

	public function testIgnoresDtstamp(): void {
		$changed = str_replace('DTSTAMP:20260101T080000Z', 'DTSTAMP:20260209T120000Z', self::CALENDAR_DATA);

		$this->assertSame(
			CalendarObjectEtagHelper::computeWithoutDtstamp(Reader::read(self::CALENDAR_DATA)),
			CalendarObjectEtagHelper::computeWithoutDtstamp(Reader::read($changed)),
		);
	}

	public function testDetectsContentChange(): void {
		$changed = str_replace('SUMMARY:Test Event', 'SUMMARY:Renamed Event', self::CALENDAR_DATA);

		$this->assertNotSame(
			CalendarObjectEtagHelper::computeWithoutDtstamp(Reader::read(self::CALENDAR_DATA)),
			CalendarObjectEtagHelper::computeWithoutDtstamp(Reader::read($changed)),
		);
	}

	public function testMatchesAfterSerializeRoundTrip(): void {
		$vObject = Reader::read("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Test//Test//EN\r\nBEGIN:VEVENT\r\nUID:etag-test\r\nDTSTAMP;X-VOBJ-ORIGINAL-TZID=America/Argentina/Buenos_Aires:20260209T120000Z\r\nDTSTART:20260301T100000Z\r\nSUMMARY:A summary that is long enough to be folded when the calendar object gets serialized\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");

		$this->assertSame(
			CalendarObjectEtagHelper::computeWithoutDtstamp($vObject),
			CalendarObjectEtagHelper::computeWithoutDtstamp(Reader::read($vObject->serialize())),
		);
	}

	public function testDoesNotModifyInput(): void {
		$vObject = Reader::read(self::CALENDAR_DATA);

		CalendarObjectEtagHelper::computeWithoutDtstamp($vObject);

		$this->assertSame('20260101T080000Z', $vObject->VEVENT->DTSTAMP->getValue());
	}
}
