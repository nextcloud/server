<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use DateTimeZone;
use OCA\DAV\CalDAV\TimeZoneFactory;
use Test\TestCase;

class TimeZoneFactoryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	public function testIsMS(): void {
		// test Microsoft time zone
		$this->assertTrue(TimeZoneFactory::isMS('Eastern Standard Time'));
		// test IANA time zone
		$this->assertFalse(TimeZoneFactory::isMS('America/Toronto'));
		// test Fake time zone
		$this->assertFalse(TimeZoneFactory::isMS('Fake Eastern Time'));
	}

	public function testToIana(): void {
		// test Microsoft time zone
		$this->assertEquals('America/Toronto', TimeZoneFactory::toIANA('Eastern Standard Time'));
		// test IANA time zone
		$this->assertEquals(null, TimeZoneFactory::toIANA('America/Toronto'));
		// test Fake time zone
		$this->assertEquals(null, TimeZoneFactory::toIANA('Fake Eastern Time'));
	}

	public function testFromName(): void {
		// test Microsoft time zone
		$this->assertEquals(new DateTimeZone('America/Toronto'), TimeZoneFactory::fromName('Eastern Standard Time'));
		// test IANA time zone
		$this->assertEquals(new DateTimeZone('America/Toronto'), TimeZoneFactory::fromName('America/Toronto'));
		// test Fake time zone
		$this->assertEquals(new DateTimeZone('UTC'), TimeZoneFactory::fromName('Fake Eastern Time'));
	}

}
