<?php
/**
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
namespace OCA\DAV\Tests\unit\CalDAV\AppCalendar;

use OCA\DAV\CalDAV\AppCalendar\AppCalendar;
use OCP\Calendar\ICalendar;
use OCP\Calendar\ICreateFromString;
use OCP\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

use function Safe\rewind;

class AppCalendarTest extends TestCase {
	private $principal = 'principals/users/foo';

	private AppCalendar $appCalendar;
	private AppCalendar $writeableAppCalendar;

	private ICalendar|MockObject $calendar;
	private ICalendar|MockObject $writeableCalendar;
	
	protected function setUp(): void {
		parent::setUp();

		$this->calendar = $this->getMockBuilder(ICalendar::class)->getMock();
		$this->calendar->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);

		$this->writeableCalendar = $this->getMockBuilder(ICreateFromString::class)->getMock();
		$this->writeableCalendar->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ | Constants::PERMISSION_CREATE);

		$this->appCalendar = new AppCalendar('dav-wrapper', $this->calendar, $this->principal);
		$this->writeableAppCalendar = new AppCalendar('dav-wrapper', $this->writeableCalendar, $this->principal);
	}

	public function testGetPrincipal():void {
		// Check that the correct name is returned
		$this->assertEquals($this->principal, $this->appCalendar->getOwner());
		$this->assertEquals($this->principal, $this->writeableAppCalendar->getOwner());
	}

	public function testDelete(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('Deleting an entry is not implemented');

		$this->appCalendar->delete();
	}

	public function testCreateFile() {
		$this->writeableCalendar->expects($this->exactly(3))
			->method('createFromString')
			->withConsecutive(['some-name', 'data'], ['other-name', ''], ['name', 'some data']);

		// pass data
		$this->assertNull($this->writeableAppCalendar->createFile('some-name', 'data'));
		// null is empty string
		$this->assertNull($this->writeableAppCalendar->createFile('other-name', null));
		// resource to data
		$fp = fopen('php://memory', 'r+');
		fwrite($fp, 'some data');
		rewind($fp);
		$this->assertNull($this->writeableAppCalendar->createFile('name', $fp));
		fclose($fp);
	}

	public function testCreateFile_readOnly() {
		// If writing is not supported
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('Creating a new entry is not allowed');

		$this->appCalendar->createFile('some-name', 'data');
	}

	public function testSetACL(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->expectExceptionMessage('Setting ACL is not supported on this node');

		$this->appCalendar->setACL([]);
	}

	public function testGetACL():void {
		$expectedRO = [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->principal,
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => $this->principal,
				'protected' => true,
			]
		];
		$expectedRW = $expectedRO;
		$expectedRW[] = [
			'privilege' => '{DAV:}write',
			'principal' => $this->principal,
			'protected' => true,
		];
		
		// Check that the correct ACL is returned (default be only readable)
		$this->assertEquals($expectedRO, $this->appCalendar->getACL());
		$this->assertEquals($expectedRW, $this->writeableAppCalendar->getACL());
	}
}
