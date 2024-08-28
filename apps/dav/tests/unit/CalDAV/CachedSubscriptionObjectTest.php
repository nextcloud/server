<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CachedSubscriptionObject;
use OCA\DAV\CalDAV\CalDavBackend;

class CachedSubscriptionObjectTest extends \Test\TestCase {
	public function testGet(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$objectData = [
			'uri' => 'foo123'
		];

		$backend->expects($this->once())
			->method('getCalendarObject')
			->with(666, 'foo123', 1)
			->willReturn([
				'calendardata' => 'BEGIN...',
			]);

		$calendarObject = new CachedSubscriptionObject($backend, $calendarInfo, $objectData);
		$this->assertEquals('BEGIN...', $calendarObject->get());
	}

	
	public function testPut(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
		$this->expectExceptionMessage('Creating objects in a cached subscription is not allowed');

		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$objectData = [
			'uri' => 'foo123'
		];

		$calendarObject = new CachedSubscriptionObject($backend, $calendarInfo, $objectData);
		$calendarObject->put('');
	}

	
	public function testDelete(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
		$this->expectExceptionMessage('Deleting objects in a cached subscription is not allowed');

		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$objectData = [
			'uri' => 'foo123'
		];

		$calendarObject = new CachedSubscriptionObject($backend, $calendarInfo, $objectData);
		$calendarObject->delete();
	}
}
