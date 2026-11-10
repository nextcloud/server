<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CachedSubscription;
use OCA\DAV\CalDAV\CachedSubscriptionImpl;
use OCA\DAV\CalDAV\CalDavBackend;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Test\TestCase;

class CachedSubscriptionImplTest extends TestCase {
	private CachedSubscription&MockObject $cachedSubscription;
	private array $cachedSubscriptionInfo;
	private CalDavBackend&MockObject $backend;
	private CachedSubscriptionImpl $cachedSubscriptionImpl;

	protected function setUp(): void {
		parent::setUp();

		$this->cachedSubscription = $this->createMock(CachedSubscription::class);
		$this->cachedSubscriptionInfo = [
			'id' => 'fancy_id_123',
			'{DAV:}displayname' => 'user readable name 123',
			'{http://apple.com/ns/ical/}calendar-color' => '#AABBCC',
			'uri' => '/this/is/a/uri',
			'source' => 'https://test.localhost/calendar1',
		];
		$this->backend = $this->createMock(CalDavBackend::class);

		$this->cachedSubscriptionImpl = new CachedSubscriptionImpl(
			$this->cachedSubscription,
			$this->cachedSubscriptionInfo,
			$this->backend
		);
	}

	public function testGetKey(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getKey(), 'fancy_id_123');
	}

	public function testGetDisplayname(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getDisplayName(), 'user readable name 123');
	}

	public function testGetDisplayColor(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getDisplayColor(), '#AABBCC');
	}

	public function testGetDisplayPropertiesWhenMissing(): void {
		$calendarInfo = $this->cachedSubscriptionInfo;
		unset($calendarInfo['{DAV:}displayname']);
		unset($calendarInfo['{http://apple.com/ns/ical/}calendar-color']);
		$cachedSubscriptionImpl = new CachedSubscriptionImpl(
			$this->cachedSubscription,
			$calendarInfo,
			$this->backend
		);

		$this->assertNull($cachedSubscriptionImpl->getDisplayName());
		$this->assertNull($cachedSubscriptionImpl->getDisplayColor());
	}

	public function testGetSource(): void {
		$this->assertEquals($this->cachedSubscriptionImpl->getSource(), 'https://test.localhost/calendar1');
	}

	public static function isTransparentData(): array {
		return [
			'transparent' => [new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT), true],
			'opaque' => [new ScheduleCalendarTransp(ScheduleCalendarTransp::OPAQUE), false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('isTransparentData')]
	public function testIsTransparent(ScheduleCalendarTransp $transp, bool $expected): void {
		$cachedSubscriptionImpl = new CachedSubscriptionImpl(
			$this->cachedSubscription,
			$this->cachedSubscriptionInfo + ['{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => $transp],
			$this->backend
		);

		$this->assertSame($expected, $cachedSubscriptionImpl->isTransparent());
	}

	public function testIsTransparentWhenMissing(): void {
		$this->assertFalse($this->cachedSubscriptionImpl->isTransparent());
	}

	public function testSearch(): void {
		$this->backend->expects($this->once())
			->method('search')
			->with($this->cachedSubscriptionInfo, 'abc', ['def'], ['ghi'], 42, 1337)
			->willReturn(['SEARCHRESULTS']);

		$result = $this->cachedSubscriptionImpl->search('abc', ['def'], ['ghi'], 42, 1337);
		$this->assertEquals($result, ['SEARCHRESULTS']);
	}

	public function testGetPermissionRead(): void {
		$this->cachedSubscription->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}read']
			]);

		$this->assertEquals(1, $this->cachedSubscriptionImpl->getPermissions());
	}
}
