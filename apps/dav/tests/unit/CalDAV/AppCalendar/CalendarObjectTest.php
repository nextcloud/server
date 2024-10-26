<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\AppCalendar;

use OCA\DAV\CalDAV\AppCalendar\AppCalendar;
use OCA\DAV\CalDAV\AppCalendar\CalendarObject;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICreateFromString;
use OCP\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

class CalendarObjectTest extends TestCase {
	private CalendarObject $calendarObject;
	private AppCalendar|MockObject $calendar;
	private ICalendar|MockObject $backend;
	private VCalendar|MockObject $vobject;

	protected function setUp(): void {
		parent::setUp();

		$this->calendar = $this->createMock(AppCalendar::class);
		$this->calendar->method('getOwner')->willReturn('owner');
		$this->calendar->method('getGroup')->willReturn('group');

		$this->backend = $this->createMock(ICalendar::class);
		$this->vobject = $this->createMock(VCalendar::class);
		$this->calendarObject = new CalendarObject($this->calendar, $this->backend, $this->vobject);
	}

	public function testGetOwner(): void {
		$this->assertEquals($this->calendarObject->getOwner(), 'owner');
	}

	public function testGetGroup(): void {
		$this->assertEquals($this->calendarObject->getGroup(), 'group');
	}

	public function testGetACL(): void {
		$this->calendar->expects($this->exactly(2))
			->method('getPermissions')
			->willReturnOnConsecutiveCalls(Constants::PERMISSION_READ, Constants::PERMISSION_ALL);

		// read only
		$this->assertEquals($this->calendarObject->getACL(), [
			[
				'privilege' => '{DAV:}read',
				'principal' => 'owner',
				'protected' => true,
			]
		]);

		// write permissions
		$this->assertEquals($this->calendarObject->getACL(), [
			[
				'privilege' => '{DAV:}read',
				'principal' => 'owner',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-content',
				'principal' => 'owner',
				'protected' => true,
			]
		]);
	}

	public function testSetACL(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->calendarObject->setACL([]);
	}

	public function testPut_readOnlyBackend(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->calendarObject->put('foo');
	}

	public function testPut_noPermissions(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$backend = $this->createMock(ICreateFromString::class);
		$calendarObject = new CalendarObject($this->calendar, $backend, $this->vobject);

		$this->calendar->expects($this->once())
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$calendarObject->put('foo');
	}

	public function testPut(): void {
		$backend = $this->createMock(ICreateFromString::class);
		$calendarObject = new CalendarObject($this->calendar, $backend, $this->vobject);

		$this->vobject->expects($this->once())
			->method('getBaseComponent')
			->willReturn((object)['UID' => 'someid']);
		$this->calendar->expects($this->once())
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_ALL);

		$backend->expects($this->once())
			->method('createFromString')
			->with('someid.ics', 'foo');
		$calendarObject->put('foo');
	}

	public function testGet(): void {
		$this->vobject->expects($this->once())
			->method('serialize')
			->willReturn('foo');
		$this->assertEquals($this->calendarObject->get(), 'foo');
	}

	public function testDelete_notWriteable(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->calendarObject->delete();
	}

	public function testDelete_noPermission(): void {
		$backend = $this->createMock(ICreateFromString::class);
		$calendarObject = new CalendarObject($this->calendar, $backend, $this->vobject);

		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$calendarObject->delete();
	}

	public function testDelete(): void {
		$backend = $this->createMock(ICreateFromString::class);
		$calendarObject = new CalendarObject($this->calendar, $backend, $this->vobject);

		$components = [(new VCalendar(['VEVENT' => ['UID' => 'someid']]))->getBaseComponent()];

		$this->calendar->expects($this->once())
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_DELETE);
		$this->vobject->expects($this->once())
			->method('getBaseComponents')
			->willReturn($components);
		$this->vobject->expects($this->once())
			->method('getBaseComponent')
			->willReturn($components[0]);

		$backend->expects($this->once())
			->method('createFromString')
			->with('someid.ics', self::callback(fn ($data): bool => preg_match('/BEGIN:VEVENT(.|\r\n)+STATUS:CANCELLED/', $data) === 1));

		$calendarObject->delete();
	}

	public function testGetName(): void {
		$this->vobject->expects($this->exactly(2))
			->method('getBaseComponent')
			->willReturnOnConsecutiveCalls((object)['UID' => 'someid'], (object)['UID' => 'someid', 'X-FILENAME' => 'real-filename.ics']);

		$this->assertEquals($this->calendarObject->getName(), 'someid.ics');
		$this->assertEquals($this->calendarObject->getName(), 'real-filename.ics');
	}

	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->calendarObject->setName('Some name');
	}
}
