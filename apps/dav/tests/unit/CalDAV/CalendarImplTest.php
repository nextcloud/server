<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarImpl;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CalendarImplTest extends TestCase {

	/** @var CalendarImpl */
	private $calendarImpl;

	/** @var Calendar | MockObject */
	private $calendar;

	/** @var array */
	private $calendarInfo;

	/** @var CalDavBackend | MockObject */
	private $backend;

	protected function setUp(): void {
		parent::setUp();

		$this->calendar = $this->createMock(Calendar::class);
		$this->calendarInfo = [
			'id' => 'fancy_id_123',
			'{DAV:}displayname' => 'user readable name 123',
			'{http://apple.com/ns/ical/}calendar-color' => '#AABBCC',
		];
		$this->backend = $this->createMock(CalDavBackend::class);

		$this->calendarImpl = new CalendarImpl($this->calendar,
			$this->calendarInfo, $this->backend);
	}


	public function testGetKey() {
		$this->assertEquals('fancy_id_123', $this->calendarImpl->getKey());
	}

	public function testGetDisplayname() {
		$this->assertEquals('user readable name 123', $this->calendarImpl->getDisplayName());
	}

	public function testGetDisplayColor() {
		$this->assertEquals('#AABBCC', $this->calendarImpl->getDisplayColor());
	}

	public function testSearch() {
		$this->backend->expects($this->once())
			->method('search')
			->with($this->calendarInfo, 'abc', ['def'], ['ghi'], 42, 1337)
			->willReturn(['SEARCHRESULTS']);

		$result = $this->calendarImpl->search('abc', ['def'], ['ghi'], 42, 1337);
		$this->assertEquals(['SEARCHRESULTS'], $result);
	}

	public function testGetPermissionRead() {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}read']
			]);

		$this->assertEquals(1, $this->calendarImpl->getPermissions());
	}

	public function testGetPermissionWrite() {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}write']
			]);

		$this->assertEquals(6, $this->calendarImpl->getPermissions());
	}

	public function testGetPermissionReadWrite() {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}read'],
				['privilege' => '{DAV:}write']
			]);

		$this->assertEquals(7, $this->calendarImpl->getPermissions());
	}

	public function testGetPermissionAll() {
		$this->calendar->expects($this->once())
			->method('getACL')
			->with()
			->willReturn([
				['privilege' => '{DAV:}all']
			]);

		$this->assertEquals(31, $this->calendarImpl->getPermissions());
	}
}
