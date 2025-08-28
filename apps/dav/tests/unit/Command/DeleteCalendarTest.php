<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarFactory;
use OCA\DAV\Command\DeleteCalendar;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class DeleteCalendarTest
 *
 * @package OCA\DAV\Tests\Command
 */
class DeleteCalendarTest extends TestCase {
	public const USER = 'user';
	public const NAME = 'calendar';

	private CalDavBackend&MockObject $calDav;
	private IUserManager&MockObject $userManager;
	private CalendarFactory&MockObject $calendarFactory;
	private DeleteCalendar $command;

	protected function setUp(): void {
		parent::setUp();

		$this->calDav = $this->createMock(CalDavBackend::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->calendarFactory = $this->createMock(CalendarFactory::class);

		$this->command = new DeleteCalendar(
			$this->calDav,
			$this->userManager,
			$this->calendarFactory,
		);
	}

	public function testInvalidUser(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'User <' . self::USER . '> is unknown.');

		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USER)
			->willReturn(false);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'name' => self::NAME,
		]);
	}

	public function testNoCalendarName(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'Please specify a calendar name or --birthday');

		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USER)
			->willReturn(true);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
		]);
	}

	public function testInvalidCalendar(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'User <' . self::USER . '> has no calendar named <' . self::NAME . '>.');

		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USER)
			->willReturn(true);
		$this->calDav->expects($this->once())
			->method('getCalendarByUri')
			->with(
				'principals/users/' . self::USER,
				self::NAME
			)
			->willReturn(null);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'name' => self::NAME,
		]);
	}

	public function testDelete(): void {
		$id = 1234;
		$calendar = [
			'id' => $id,
			'principaluri' => 'principals/users/' . self::USER,
			'uri' => self::NAME,
		];

		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USER)
			->willReturn(true);
		$this->calDav->expects($this->once())
			->method('getCalendarByUri')
			->with(
				'principals/users/' . self::USER,
				self::NAME
			)
			->willReturn($calendar);
		$calendarObj = $this->createMock(Calendar::class);
		$this->calendarFactory->expects(self::once())
			->method('createCalendar')
			->with($calendar)
			->willReturn($calendarObj);
		$calendarObj->expects(self::never())
			->method('disableTrashbin');
		$calendarObj->expects(self::once())
			->method('delete');

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'name' => self::NAME,
		]);
	}

	public function testForceDelete(): void {
		$id = 1234;
		$calendar = [
			'id' => $id,
			'principaluri' => 'principals/users/' . self::USER,
			'uri' => self::NAME
		];

		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USER)
			->willReturn(true);
		$this->calDav->expects($this->once())
			->method('getCalendarByUri')
			->with(
				'principals/users/' . self::USER,
				self::NAME
			)
			->willReturn($calendar);
		$calendarObj = $this->createMock(Calendar::class);
		$this->calendarFactory->expects(self::once())
			->method('createCalendar')
			->with($calendar)
			->willReturn($calendarObj);
		$calendarObj->expects(self::once())
			->method('disableTrashbin');
		$calendarObj->expects(self::once())
			->method('delete');

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'name' => self::NAME,
			'-f' => true
		]);
	}

	public function testDeleteBirthday(): void {
		$id = 1234;
		$calendar = [
			'id' => $id,
			'principaluri' => 'principals/users/' . self::USER,
			'uri' => BirthdayService::BIRTHDAY_CALENDAR_URI,
			'{DAV:}displayname' => 'Test',
		];

		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USER)
			->willReturn(true);
		$this->calDav->expects($this->once())
			->method('getCalendarByUri')
			->with(
				'principals/users/' . self::USER,
				BirthdayService::BIRTHDAY_CALENDAR_URI
			)
			->willReturn($calendar);
		$calendarObj = $this->createMock(Calendar::class);
		$this->calendarFactory->expects(self::once())
			->method('createCalendar')
			->with($calendar)
			->willReturn($calendarObj);
		$calendarObj->expects(self::never())
			->method('disableTrashbin');
		$calendarObj->expects(self::once())
			->method('delete');

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'--birthday' => true,
		]);
	}

	public function testBirthdayHasPrecedence(): void {
		$calendar = [
			'id' => 1234,
			'principaluri' => 'principals/users/' . self::USER,
			'uri' => BirthdayService::BIRTHDAY_CALENDAR_URI,
			'{DAV:}displayname' => 'Test',
		];
		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USER)
			->willReturn(true);
		$this->calDav->expects($this->once())
			->method('getCalendarByUri')
			->with(
				'principals/users/' . self::USER,
				BirthdayService::BIRTHDAY_CALENDAR_URI
			)
			->willReturn($calendar);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'name' => self::NAME,
			'--birthday' => true,
		]);
	}
}
