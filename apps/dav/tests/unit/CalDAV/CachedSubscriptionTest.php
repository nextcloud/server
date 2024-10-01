<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CachedSubscription;
use OCA\DAV\CalDAV\CachedSubscriptionObject;
use OCA\DAV\CalDAV\CalDavBackend;
use Sabre\DAV\PropPatch;

class CachedSubscriptionTest extends \Test\TestCase {
	public function testGetACL(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$calendar = new CachedSubscription($backend, $calendarInfo);
		$this->assertEquals([
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user1',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user1/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user1/calendar-proxy-read',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}read-free-busy',
				'principal' => '{DAV:}authenticated',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => 'user1',
				'protected' => 'true'
			]
		], $calendar->getACL());
	}

	public function testGetChildACL(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$calendar = new CachedSubscription($backend, $calendarInfo);
		$this->assertEquals([
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user1',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user1/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user1/calendar-proxy-read',
				'protected' => true,
			]
		], $calendar->getChildACL());
	}

	public function testGetOwner(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$calendar = new CachedSubscription($backend, $calendarInfo);
		$this->assertEquals('user1', $calendar->getOwner());
	}

	public function testDelete(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$backend->expects($this->once())
			->method('deleteSubscription')
			->with(666);

		$calendar = new CachedSubscription($backend, $calendarInfo);
		$calendar->delete();
	}

	public function testPropPatch(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];
		$propPatch = $this->createMock(PropPatch::class);

		$backend->expects($this->once())
			->method('updateSubscription')
			->with(666, $propPatch);

		$calendar = new CachedSubscription($backend, $calendarInfo);
		$calendar->propPatch($propPatch);
	}


	public function testGetChild(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);
		$this->expectExceptionMessage('Calendar object not found');

		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$backend->expects($this->exactly(2))
			->method('getCalendarObject')
			->withConsecutive(
				[666, 'foo1', 1],
				[666, 'foo2', 1],
			)
			->willReturnOnConsecutiveCalls(
				[
					'id' => 99,
					'uri' => 'foo1'
				],
				null
			);

		$calendar = new CachedSubscription($backend, $calendarInfo);

		$first = $calendar->getChild('foo1');
		$this->assertInstanceOf(CachedSubscriptionObject::class, $first);

		$calendar->getChild('foo2');
	}

	public function testGetChildren(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$backend->expects($this->once())
			->method('getCalendarObjects')
			->with(666, 1)
			->willReturn([
				[
					'id' => 99,
					'uri' => 'foo1'
				],
				[
					'id' => 100,
					'uri' => 'foo2'
				],
			]);

		$calendar = new CachedSubscription($backend, $calendarInfo);

		$res = $calendar->getChildren();
		$this->assertCount(2, $res);
		$this->assertInstanceOf(CachedSubscriptionObject::class, $res[0]);
		$this->assertInstanceOf(CachedSubscriptionObject::class, $res[1]);
	}

	public function testGetMultipleChildren(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$backend->expects($this->once())
			->method('getMultipleCalendarObjects')
			->with(666, ['foo1', 'foo2'], 1)
			->willReturn([
				[
					'id' => 99,
					'uri' => 'foo1'
				],
				[
					'id' => 100,
					'uri' => 'foo2'
				],
			]);

		$calendar = new CachedSubscription($backend, $calendarInfo);

		$res = $calendar->getMultipleChildren(['foo1', 'foo2']);
		$this->assertCount(2, $res);
		$this->assertInstanceOf(CachedSubscriptionObject::class, $res[0]);
		$this->assertInstanceOf(CachedSubscriptionObject::class, $res[1]);
	}


	public function testCreateFile(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);
		$this->expectExceptionMessage('Creating objects in cached subscription is not allowed');

		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$calendar = new CachedSubscription($backend, $calendarInfo);
		$calendar->createFile('foo', []);
	}

	public function testChildExists(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$backend->expects($this->exactly(2))
			->method('getCalendarObject')
			->withConsecutive(
				[666, 'foo1', 1],
				[666, 'foo2', 1],
			)
			->willReturnOnConsecutiveCalls(
				[
					'id' => 99,
					'uri' => 'foo1'
				],
				null
			);

		$calendar = new CachedSubscription($backend, $calendarInfo);

		$this->assertEquals(true, $calendar->childExists('foo1'));
		$this->assertEquals(false, $calendar->childExists('foo2'));
	}

	public function testCalendarQuery(): void {
		$backend = $this->createMock(CalDavBackend::class);
		$calendarInfo = [
			'{http://owncloud.org/ns}owner-principal' => 'user1',
			'principaluri' => 'user2',
			'id' => 666,
			'uri' => 'cal',
		];

		$backend->expects($this->once())
			->method('calendarQuery')
			->with(666, ['foo'], 1)
			->willReturn([99]);

		$calendar = new CachedSubscription($backend, $calendarInfo);

		$this->assertEquals([99], $calendar->calendarQuery(['foo']));
	}
}
