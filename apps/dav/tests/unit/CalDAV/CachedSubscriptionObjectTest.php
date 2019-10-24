<?php
/**
 * @copyright Copyright (c) 2018 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CachedSubscriptionObject;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarImpl;

class CachedSubscriptionObjectTest extends \Test\TestCase {

	public function testGet() {
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
			->will($this->returnValue([
				'calendardata' => 'BEGIN...',
			]));

		$calendarObject = new CachedSubscriptionObject($backend, $calendarInfo, $objectData);
		$this->assertEquals('BEGIN...', $calendarObject->get());
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
	 * @expectedExceptionMessage Creating objects in a cached subscription is not allowed
	 */
	public function testPut() {
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

	/**
	 * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
	 * @expectedExceptionMessage Deleting objects in a cached subscription is not allowed
	 */
	public function testDelete() {
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