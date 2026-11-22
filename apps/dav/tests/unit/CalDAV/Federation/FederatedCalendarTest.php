<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\CalDAV\Federation;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Federation\FederatedCalendar;
use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
use OCA\DAV\CalDAV\Federation\FederatedCalendarObject;
use OCA\DAV\CalDAV\Federation\FederatedCalendarSyncService;
use OCP\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;
use Test\TestCase;

class FederatedCalendarTest extends TestCase {
	private FederatedCalendar $federatedCalendar;

	private FederatedCalendarMapper&MockObject $federatedCalendarMapper;
	private FederatedCalendarSyncService&MockObject $federatedCalendarService;
	private CalDavBackend&MockObject $caldavBackend;
	private FederatedCalendarEntity $federationInfo;

	protected function setUp(): void {
		parent::setUp();

		$this->federatedCalendarMapper = $this->createMock(FederatedCalendarMapper::class);
		$this->federatedCalendarService = $this->createMock(FederatedCalendarSyncService::class);
		$this->caldavBackend = $this->createMock(CalDavBackend::class);

		$this->federationInfo = new FederatedCalendarEntity();
		$this->federationInfo->setId(10);
		$this->federationInfo->setPrincipaluri('principals/users/user1');
		$this->federationInfo->setUri('calendar-uri');
		$this->federationInfo->setDisplayName('Federated Calendar');
		$this->federationInfo->setColor('#ff0000');
		$this->federationInfo->setSharedBy('user2@nextcloud.remote');
		$this->federationInfo->setSharedByDisplayName('User 2');
		$this->federationInfo->setPermissions(Constants::PERMISSION_READ);
		$this->federationInfo->setLastSync(1234567890);

		$this->federatedCalendarMapper->method('findByUri')
			->with('principals/users/user1', 'calendar-uri')
			->willReturn($this->federationInfo);

		$calendarInfo = [
			'principaluri' => 'principals/users/user1',
			'id' => 10,
			'uri' => 'calendar-uri',
		];

		$this->federatedCalendar = new FederatedCalendar(
			$this->federatedCalendarMapper,
			$this->federatedCalendarService,
			$this->caldavBackend,
			$calendarInfo,
		);
	}

	public function testGetResourceId(): void {
		$this->assertEquals(10, $this->federatedCalendar->getResourceId());
	}

	public function testGetName(): void {
		$this->assertEquals('calendar-uri', $this->federatedCalendar->getName());
	}

	public function testSetName(): void {
		$this->expectException(MethodNotAllowed::class);
		$this->expectExceptionMessage('Renaming federated calendars is not allowed');
		$this->federatedCalendar->setName('new-name');
	}

	public function testGetPrincipalURI(): void {
		$this->assertEquals('principals/users/user1', $this->federatedCalendar->getPrincipalURI());
	}

	public function testGetOwner(): void {
		$expected = 'principals/remote-users/' . base64_encode('user2@nextcloud.remote');
		$this->assertEquals($expected, $this->federatedCalendar->getOwner());
	}

	public function testGetGroup(): void {
		$this->assertNull($this->federatedCalendar->getGroup());
	}

	public function testGetACLWithReadOnlyPermissions(): void {
		$this->federationInfo->setPermissions(Constants::PERMISSION_READ);

		$acl = $this->federatedCalendar->getACL();

		$this->assertCount(3, $acl);
		// Check basic read permissions
		$this->assertEquals('{DAV:}read', $acl[0]['privilege']);
		$this->assertTrue($acl[0]['protected']);
		$this->assertEquals('{DAV:}read-acl', $acl[1]['privilege']);
		$this->assertTrue($acl[1]['protected']);
		$this->assertEquals('{DAV:}write-properties', $acl[2]['privilege']);
		$this->assertTrue($acl[2]['protected']);
	}

	public function testGetACLWithCreatePermission(): void {
		$this->federationInfo->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_CREATE);

		$acl = $this->federatedCalendar->getACL();

		$this->assertCount(4, $acl);
		// Check that create permission is added
		$privileges = array_column($acl, 'privilege');
		$this->assertContains('{DAV:}bind', $privileges);
	}

	public function testGetACLWithUpdatePermission(): void {
		$this->federationInfo->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE);

		$acl = $this->federatedCalendar->getACL();

		$this->assertCount(4, $acl);
		// Check that update permission is added (write-content, not write-properties which is already in base ACL)
		$privileges = array_column($acl, 'privilege');
		$this->assertContains('{DAV:}write-content', $privileges);
	}

	public function testGetACLWithDeletePermission(): void {
		$this->federationInfo->setPermissions(Constants::PERMISSION_READ | Constants::PERMISSION_DELETE);

		$acl = $this->federatedCalendar->getACL();

		$this->assertCount(4, $acl);
		// Check that delete permission is added
		$privileges = array_column($acl, 'privilege');
		$this->assertContains('{DAV:}unbind', $privileges);
	}

	public function testGetACLWithAllPermissions(): void {
		$this->federationInfo->setPermissions(
			Constants::PERMISSION_READ
			| Constants::PERMISSION_CREATE
			| Constants::PERMISSION_UPDATE
			| Constants::PERMISSION_DELETE
		);

		$acl = $this->federatedCalendar->getACL();

		$this->assertCount(6, $acl);
		$privileges = array_column($acl, 'privilege');
		$this->assertContains('{DAV:}read', $privileges);
		$this->assertContains('{DAV:}bind', $privileges);
		$this->assertContains('{DAV:}write-content', $privileges);
		$this->assertContains('{DAV:}write-properties', $privileges);
		$this->assertContains('{DAV:}unbind', $privileges);
	}

	public function testSetACL(): void {
		$this->expectException(MethodNotAllowed::class);
		$this->expectExceptionMessage('Changing ACLs on federated calendars is not allowed');
		$this->federatedCalendar->setACL([]);
	}

	public function testGetSupportedPrivilegeSet(): void {
		$this->assertNull($this->federatedCalendar->getSupportedPrivilegeSet());
	}

	public function testGetProperties(): void {
		$properties = $this->federatedCalendar->getProperties([
			'{DAV:}displayname',
			'{http://apple.com/ns/ical/}calendar-color',
		]);

		$this->assertEquals('Federated Calendar', $properties['{DAV:}displayname']);
		$this->assertEquals('#ff0000', $properties['{http://apple.com/ns/ical/}calendar-color']);
	}

	public function testPropPatchWithDisplayName(): void {
		$propPatch = $this->createMock(PropPatch::class);
		$propPatch->method('getMutations')
			->willReturn([
				'{DAV:}displayname' => 'New Calendar Name',
			]);

		$this->federatedCalendarMapper->expects(self::once())
			->method('update')
			->willReturnCallback(function (FederatedCalendarEntity $entity) {
				$this->assertEquals('New Calendar Name', $entity->getDisplayName());
				return $entity;
			});

		$propPatch->expects(self::once())
			->method('setResultCode')
			->with('{DAV:}displayname', 200);

		$this->federatedCalendar->propPatch($propPatch);
	}

	public function testPropPatchWithColor(): void {
		$propPatch = $this->createMock(PropPatch::class);
		$propPatch->method('getMutations')
			->willReturn([
				'{http://apple.com/ns/ical/}calendar-color' => '#00ff00',
			]);

		$this->federatedCalendarMapper->expects(self::once())
			->method('update')
			->willReturnCallback(function (FederatedCalendarEntity $entity) {
				$this->assertEquals('#00ff00', $entity->getColor());
				return $entity;
			});

		$propPatch->expects(self::once())
			->method('setResultCode')
			->with('{http://apple.com/ns/ical/}calendar-color', 200);

		$this->federatedCalendar->propPatch($propPatch);
	}

	public function testPropPatchWithNoMutations(): void {
		$propPatch = $this->createMock(PropPatch::class);
		$propPatch->method('getMutations')
			->willReturn([]);

		$this->federatedCalendarMapper->expects(self::never())
			->method('update');

		$propPatch->expects(self::never())
			->method('handle');

		$this->federatedCalendar->propPatch($propPatch);
	}

	public function testGetChildACL(): void {
		$this->assertEquals($this->federatedCalendar->getACL(), $this->federatedCalendar->getChildACL());
	}

	public function testGetLastModified(): void {
		$this->assertEquals(1234567890, $this->federatedCalendar->getLastModified());
	}

	public function testDelete(): void {
		$this->federatedCalendarMapper->expects(self::once())
			->method('deleteById')
			->with(10);

		$this->federatedCalendar->delete();
	}

	public function testCreateDirectory(): void {
		$this->expectException(MethodNotAllowed::class);
		$this->expectExceptionMessage('Creating nested collection is not allowed');
		$this->federatedCalendar->createDirectory('test');
	}

	public function testCalendarQuery(): void {
		$filters = ['comp-filter' => ['name' => 'VEVENT']];
		$expectedUris = ['event1.ics', 'event2.ics'];

		$this->caldavBackend->expects(self::once())
			->method('calendarQuery')
			->with(10, $filters, 2) // 2 is CALENDAR_TYPE_FEDERATED
			->willReturn($expectedUris);

		$result = $this->federatedCalendar->calendarQuery($filters);
		$this->assertEquals($expectedUris, $result);
	}

	public function testGetChild(): void {
		$objectData = [
			'id' => 1,
			'uri' => 'event1.ics',
			'calendardata' => 'BEGIN:VCALENDAR...',
		];

		$this->caldavBackend->expects(self::once())
			->method('getCalendarObject')
			->with(10, 'event1.ics', 2) // 2 is CALENDAR_TYPE_FEDERATED
			->willReturn($objectData);

		$child = $this->federatedCalendar->getChild('event1.ics');
		$this->assertInstanceOf(FederatedCalendarObject::class, $child);
	}

	public function testGetChildNotFound(): void {
		$this->caldavBackend->expects(self::once())
			->method('getCalendarObject')
			->with(10, 'nonexistent.ics', 2)
			->willReturn(null);

		$this->expectException(NotFound::class);
		$this->federatedCalendar->getChild('nonexistent.ics');
	}

	public function testGetChildren(): void {
		$objects = [
			['id' => 1, 'uri' => 'event1.ics', 'calendardata' => 'BEGIN:VCALENDAR...'],
			['id' => 2, 'uri' => 'event2.ics', 'calendardata' => 'BEGIN:VCALENDAR...'],
		];

		$this->caldavBackend->expects(self::once())
			->method('getCalendarObjects')
			->with(10, 2) // 2 is CALENDAR_TYPE_FEDERATED
			->willReturn($objects);

		$children = $this->federatedCalendar->getChildren();
		$this->assertCount(2, $children);
		$this->assertInstanceOf(FederatedCalendarObject::class, $children[0]);
		$this->assertInstanceOf(FederatedCalendarObject::class, $children[1]);
	}

	public function testGetMultipleChildren(): void {
		$paths = ['event1.ics', 'event2.ics'];
		$objects = [
			['id' => 1, 'uri' => 'event1.ics', 'calendardata' => 'BEGIN:VCALENDAR...'],
			['id' => 2, 'uri' => 'event2.ics', 'calendardata' => 'BEGIN:VCALENDAR...'],
		];

		$this->caldavBackend->expects(self::once())
			->method('getMultipleCalendarObjects')
			->with(10, $paths, 2) // 2 is CALENDAR_TYPE_FEDERATED
			->willReturn($objects);

		$children = $this->federatedCalendar->getMultipleChildren($paths);
		$this->assertCount(2, $children);
		$this->assertInstanceOf(FederatedCalendarObject::class, $children[0]);
		$this->assertInstanceOf(FederatedCalendarObject::class, $children[1]);
	}

	public function testChildExists(): void {
		$this->caldavBackend->expects(self::once())
			->method('getCalendarObject')
			->with(10, 'event1.ics', 2)
			->willReturn(['id' => 1, 'uri' => 'event1.ics']);

		$result = $this->federatedCalendar->childExists('event1.ics');
		$this->assertTrue($result);
	}

	public function testChildNotExists(): void {
		$this->caldavBackend->expects(self::once())
			->method('getCalendarObject')
			->with(10, 'nonexistent.ics', 2)
			->willReturn(null);

		$result = $this->federatedCalendar->childExists('nonexistent.ics');
		$this->assertFalse($result);
	}

	public function testCreateFile(): void {
		$calendarData = 'BEGIN:VCALENDAR...END:VCALENDAR';
		$remoteEtag = '"remote-etag-123"';
		$localEtag = '"local-etag-456"';

		$this->federatedCalendarService->expects(self::once())
			->method('createCalendarObject')
			->with($this->federationInfo, 'event1.ics', $calendarData)
			->willReturn($remoteEtag);

		$this->caldavBackend->expects(self::once())
			->method('createCalendarObject')
			->with(10, 'event1.ics', $calendarData, 2)
			->willReturn($localEtag);

		$result = $this->federatedCalendar->createFile('event1.ics', $calendarData);
		$this->assertEquals($localEtag, $result);
	}

	public function testUpdateFile(): void {
		$calendarData = 'BEGIN:VCALENDAR...UPDATED...END:VCALENDAR';
		$remoteEtag = '"remote-etag-updated"';
		$localEtag = '"local-etag-updated"';

		$this->federatedCalendarService->expects(self::once())
			->method('updateCalendarObject')
			->with($this->federationInfo, 'event1.ics', $calendarData)
			->willReturn($remoteEtag);

		$this->caldavBackend->expects(self::once())
			->method('updateCalendarObject')
			->with(10, 'event1.ics', $calendarData, 2)
			->willReturn($localEtag);

		$result = $this->federatedCalendar->updateFile('event1.ics', $calendarData);
		$this->assertEquals($localEtag, $result);
	}

	public function testDeleteFile(): void {
		$this->federatedCalendarService->expects(self::once())
			->method('deleteCalendarObject')
			->with($this->federationInfo, 'event1.ics');

		$this->caldavBackend->expects(self::once())
			->method('deleteCalendarObject')
			->with(10, 'event1.ics', 2);

		$this->federatedCalendar->deleteFile('event1.ics');
	}
}
