<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\Unit\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Migration\ValueFix;
use OCP\ILogger;
use Sabre\VObject\InvalidDataException;
use Test\TestCase;

class ValueFixTest extends TestCase  {

	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var CalDavBackend|\PHPUnit_Framework_MockObject_MockObject */
	private $backend;

	/** @var string */
	private $invalid = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.11.2//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
TRANSP:OPAQUE
DTEND;VALUE=:20151223T223000Z
LAST-MODIFIED:20151214T091032Z
ORGANIZER;CN="User 1":mailto:user1@example.com
UID:1234567890@example.com
DTSTAMP:20151214T091032Z
STATUS:CONFIRMED
SEQUENCE:0
SUMMARY:Ein Geburtstag 
DTSTART;VALUE=:20151223T173000Z
X-APPLE-TRAVEL-ADVISORY-BEHAVIOR:AUTOMATIC
CREATED;VALUE=:20151214T091032Z
END:VEVENT
END:VCALENDAR';

	/** @var string */
	private $valid = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//Mac OS X 10.11.2//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
TRANSP:OPAQUE
DTEND:20151223T223000Z
LAST-MODIFIED:20151214T091032Z
ORGANIZER;CN="User 1":mailto:user1@example.com
UID:1234567890@example.com
DTSTAMP:20151214T091032Z
STATUS:CONFIRMED
SEQUENCE:0
SUMMARY:Ein Geburtstag 
DTSTART:20151223T173000Z
X-APPLE-TRAVEL-ADVISORY-BEHAVIOR:AUTOMATIC
CREATED:20151214T091032Z
END:VEVENT
END:VCALENDAR';

	/** @var ValueFix */
	private $job;

	public function setUp() {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->backend = $this->createMock(CalDavBackend::class);
		$this->job = new ValueFix(
			$this->backend,
			$this->logger
		);
	}

	public function testRunInvalid() {
		$calendars = [['id' => 42]];
		$objects = [['uri' => 'myuri']];

		$this->backend->method('getCalendarsForUser')
			->with($this->equalTo('principals/users/u1'))
			->willReturn($calendars);

		$this->backend->method('getCalendarObjects')
			->with($this->equalTo(42))
			->willReturn($objects);

		$this->backend->method('getCalendarObject')
			->with(
				$this->equalTo(42),
				$this->equalTo('myuri')
			)->willReturn([
				'calendardata' => $this->invalid
			]);

		$this->backend->expects($this->once())
			->method('getDenormalizedData')
			->with($this->valid);

		$this->backend->expects($this->once())
			->method('updateCalendarObject')
			->with(
				$this->equalTo(42),
				$this->equalTo('myuri'),
				$this->equalTo($this->valid)
			);

		$this->job->run(['user' => 'u1']);
	}

	public function testRunValid() {
		$calendars = [['id' => 42]];
		$objects = [['uri' => 'myuri']];

		$this->backend->method('getCalendarsForUser')
			->with($this->equalTo('principals/users/u1'))
			->willReturn($calendars);

		$this->backend->method('getCalendarObjects')
			->with($this->equalTo(42))
			->willReturn($objects);

		$this->backend->method('getCalendarObject')
			->with(
				$this->equalTo(42),
				$this->equalTo('myuri')
			)->willReturn([
				'calendardata' => $this->valid
			]);

		$this->backend->expects($this->never())
			->method('getDenormalizedData');

		$this->backend->expects($this->never())
			->method('updateCalendarObject');

		$this->job->run(['user' => 'u1']);
	}

	public function testRunStillInvalid() {
		$calendars = [['id' => 42]];
		$objects = [['uri' => 'myuri']];

		$this->backend->method('getCalendarsForUser')
			->with($this->equalTo('principals/users/u1'))
			->willReturn($calendars);

		$this->backend->method('getCalendarObjects')
			->with($this->equalTo(42))
			->willReturn($objects);

		$this->backend->method('getCalendarObject')
			->with(
				$this->equalTo(42),
				$this->equalTo('myuri')
			)->willReturn([
				'calendardata' => $this->invalid
			]);

		$this->backend->expects($this->once())
			->method('getDenormalizedData')
			->with($this->valid)
			->willThrowException(new InvalidDataException());

		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Calendar object for calendar {cal} with uri {uri} still invalid'),
				$this->equalTo([
					'app'=> 'dav',
					'cal' => 42,
					'uri' => 'myuri',
				])
			);

		$this->backend->expects($this->never())
			->method('updateCalendarObject');

		$this->job->run(['user' => 'u1']);
	}
}
