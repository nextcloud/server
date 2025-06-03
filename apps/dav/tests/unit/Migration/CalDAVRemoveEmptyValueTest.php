<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Migration\CalDAVRemoveEmptyValue;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\VObject\InvalidDataException;
use Test\TestCase;

/**
 * Class CalDAVRemoveEmptyValueTest
 *
 * @package OCA\DAV\Tests\Unit\DAV\Migration
 * @group DB
 */
class CalDAVRemoveEmptyValueTest extends TestCase {
	private LoggerInterface&MockObject $logger;
	private CalDavBackend&MockObject $backend;
	private IOutput&MockObject $output;
	private string $invalid = 'BEGIN:VCALENDAR
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

	private string $valid = 'BEGIN:VCALENDAR
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

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->backend = $this->createMock(CalDavBackend::class);
		$this->output = $this->createMock(IOutput::class);
	}

	public function testRunAllValid(): void {
		/** @var CalDAVRemoveEmptyValue&MockObject $step */
		$step = $this->getMockBuilder(CalDAVRemoveEmptyValue::class)
			->setConstructorArgs([
				Server::get(IDBConnection::class),
				$this->backend,
				$this->logger
			])
			->onlyMethods(['getInvalidObjects'])
			->getMock();

		$step->expects($this->once())
			->method('getInvalidObjects')
			->willReturn([]);

		$this->output->expects($this->once())
			->method('startProgress')
			->with(0);
		$this->output->expects($this->once())
			->method('finishProgress');

		$step->run($this->output);
	}

	public function testRunInvalid(): void {
		/** @var CalDAVRemoveEmptyValue&MockObject $step */
		$step = $this->getMockBuilder(CalDAVRemoveEmptyValue::class)
			->setConstructorArgs([
				Server::get(IDBConnection::class),
				$this->backend,
				$this->logger
			])
			->onlyMethods(['getInvalidObjects'])
			->getMock();

		$step->expects($this->once())
			->method('getInvalidObjects')
			->willReturn([
				['calendarid' => '42', 'uri' => 'myuri'],
			]);

		$this->output->expects($this->once())
			->method('startProgress')
			->with(1);
		$this->output->expects($this->once())
			->method('finishProgress');

		$this->backend->expects($this->exactly(1))
			->method('getCalendarObject')
			->with(42, 'myuri')
			->willReturn([
				'calendardata' => $this->invalid
			]);

		$this->output->expects($this->exactly(1))
			->method('advance');
		$this->backend->expects($this->exactly(1))
			->method('getDenormalizedData')
			->with($this->valid);

		$this->backend->expects($this->exactly(1))
			->method('updateCalendarObject')
			->with(42, 'myuri', $this->valid);

		$step->run($this->output);
	}

	public function testRunValid(): void {
		/** @var CalDAVRemoveEmptyValue&MockObject $step */
		$step = $this->getMockBuilder(CalDAVRemoveEmptyValue::class)
			->setConstructorArgs([
				Server::get(IDBConnection::class),
				$this->backend,
				$this->logger
			])
			->onlyMethods(['getInvalidObjects'])
			->getMock();

		$step->expects($this->once())
			->method('getInvalidObjects')
			->willReturn([
				['calendarid' => '42', 'uri' => 'myuri'],
			]);

		$this->output->expects($this->once())
			->method('startProgress')
			->with(1);
		$this->output->expects($this->once())
			->method('finishProgress');


		$this->backend->expects($this->exactly(1))
			->method('getCalendarObject')
			->with(42, 'myuri')
			->willReturn([
				'calendardata' => $this->valid
			]);

		$this->output->expects($this->never())
			->method('advance');
		$this->backend->expects($this->never())
			->method('getDenormalizedData');

		$this->backend->expects($this->never())
			->method('updateCalendarObject');

		$step->run($this->output);
	}

	public function testRunStillInvalid(): void {
		/** @var CalDAVRemoveEmptyValue&MockObject $step */
		$step = $this->getMockBuilder(CalDAVRemoveEmptyValue::class)
			->setConstructorArgs([
				Server::get(IDBConnection::class),
				$this->backend,
				$this->logger
			])
			->onlyMethods(['getInvalidObjects'])
			->getMock();

		$step->expects($this->once())
			->method('getInvalidObjects')
			->willReturn([
				['calendarid' => '42', 'uri' => 'myuri'],
			]);

		$this->output->expects($this->once())
			->method('startProgress')
			->with(1);
		$this->output->expects($this->once())
			->method('finishProgress');


		$this->backend->expects($this->exactly(1))
			->method('getCalendarObject')
			->with(42, 'myuri')
			->willReturn([
				'calendardata' => $this->invalid
			]);

		$this->output->expects($this->exactly(1))
			->method('advance');
		$this->backend->expects($this->exactly(1))
			->method('getDenormalizedData')
			->with($this->valid)
			->willThrowException(new InvalidDataException());

		$this->backend->expects($this->never())
			->method('updateCalendarObject');

		$step->run($this->output);
	}
}
