<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use DateTime;
use DateTimeImmutable;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\EventReaderRRule;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class EventReaderRRuleTest extends TestCase {
	public static function dataConcludes(): array {
		return [
			'until' => ['FREQ=DAILY;UNTIL=20300110T100000Z', '2030-01-10T10:00:00+00:00'],
			'small count' => ['FREQ=DAILY;COUNT=10', '2030-01-10T10:00:00+00:00'],
			'count at the recurrence limit' => ['FREQ=DAILY;COUNT=3500', '2039-08-01T10:00:00+00:00'],
			'count above the recurrence limit' => ['FREQ=DAILY;COUNT=3501', CalDavBackend::MAX_DATE],
			'huge count' => ['FREQ=HOURLY;COUNT=9000000000000000000', CalDavBackend::MAX_DATE],
			'huge count with unhandled frequency' => ['FREQ=SECONDLY;COUNT=9000000000000000000', CalDavBackend::MAX_DATE],
		];
	}

	#[DataProvider('dataConcludes')]
	public function testConcludes(string $rule, string $expected): void {
		$rrule = new EventReaderRRule($rule, new DateTimeImmutable('2030-01-01T10:00:00+00:00'));

		$this->assertEquals(new DateTime($expected), $rrule->concludes());
	}

	public function testConcludesEndlessRule(): void {
		$rrule = new EventReaderRRule('FREQ=WEEKLY', new DateTimeImmutable('2030-01-01T10:00:00+00:00'));

		$this->assertNull($rrule->concludes());
	}
}
