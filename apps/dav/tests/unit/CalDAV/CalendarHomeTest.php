<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2017, Georg Ehrke
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

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarHome;
use Sabre\DAV\MkCol;
use Test\TestCase;

class CalendarHomeTest extends TestCase {

	/** @var CalDavBackend | \PHPUnit_Framework_MockObject_MockObject */
	private $backend;

	/** @var array */
	private $principalInfo = [];

	/** @var CalendarHome */
	private $calendarHome;

	protected function setUp() {
		parent::setUp();

		$this->backend = $this->createMock(CalDavBackend::class);
		$this->principalInfo = [
			'uri' => 'user-principal-123',
		];

		$this->calendarHome = new CalendarHome($this->backend,
			$this->principalInfo);
	}

	public function testCreateCalendarValidName() {
		/** @var MkCol | \PHPUnit_Framework_MockObject_MockObject $mkCol */
		$mkCol = $this->createMock(MkCol::class);

		$mkCol->method('getResourceType')
			->will($this->returnValue(['{DAV:}collection',
				'{urn:ietf:params:xml:ns:caldav}calendar']));
		$mkCol->method('getRemainingValues')
			->will($this->returnValue(['... properties ...']));

		$this->backend->expects($this->once())
			->method('createCalendar')
			->with('user-principal-123', 'name123', ['... properties ...']);

		$this->calendarHome->createExtendedCollection('name123', $mkCol);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
	 * @expectedExceptionMessage The resource you tried to create has a reserved name
	 */
	public function testCreateCalendarReservedName() {
		/** @var MkCol | \PHPUnit_Framework_MockObject_MockObject $mkCol */
		$mkCol = $this->createMock(MkCol::class);

		$this->calendarHome->createExtendedCollection('contact_birthdays', $mkCol);
	}
}
