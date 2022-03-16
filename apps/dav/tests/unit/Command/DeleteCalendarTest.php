<?php

declare(strict_types=1);
/**
 *
 * @copyright Copyright (c) 2021, Mattia Narducci (mattianarducci1@gmail.com)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDav\CalDavBackend;
use OCA\DAV\Command\DeleteCalendar;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
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

	/** @var CalDavBackend|MockObject */
	private $calDav;

	/** @var IConfig|MockObject */
	private $config;

	/** @var IL10N|MockObject */
	private $l10n;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var DeleteCalendar */
	private $command;
	
	/** @var MockObject|LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->calDav = $this->createMock(CalDavBackend::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->command = new DeleteCalendar(
			$this->calDav,
			$this->config,
			$this->l10n,
			$this->userManager,
			$this->logger
		);
	}

	public function testInvalidUser() {
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

	public function testNoCalendarName() {
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

	public function testInvalidCalendar() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(
			'User <' . self::USER . '> has no calendar named <' . self::NAME .  '>.');

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

	public function testDelete() {
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
		$this->calDav->expects($this->once())
			->method('deleteCalendar')
			->with($id, false);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'name' => self::NAME,
		]);
	}

	public function testForceDelete() {
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
		$this->calDav->expects($this->once())
			->method('deleteCalendar')
			->with($id, true);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'name' => self::NAME,
			'-f' => true
		]);
	}

	public function testDeleteBirthday() {
		$id = 1234;
		$calendar = [
			'id' => $id,
			'principaluri' => 'principals/users/' . self::USER,
			'uri' => BirthdayService::BIRTHDAY_CALENDAR_URI
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
		$this->calDav->expects($this->once())
			->method('deleteCalendar')
			->with($id);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USER,
			'--birthday' => true,
		]);
	}

	public function testBirthdayHasPrecedence() {
		$calendar = [
			'id' => 1234,
			'principaluri' => 'principals/users/' . self::USER,
			'uri' => BirthdayService::BIRTHDAY_CALENDAR_URI
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
