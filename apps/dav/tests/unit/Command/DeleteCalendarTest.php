<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
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

	private CalDavBackend&MockObject $calDav;
	private IConfig&MockObject $config;
	private IL10N&MockObject $l10n;
	private IUserManager&MockObject $userManager;
	private LoggerInterface&MockObject $logger;
	private DeleteCalendar $command;

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
		$this->calDav->expects($this->once())
			->method('deleteCalendar')
			->with($id, false);

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
		$this->calDav->expects($this->once())
			->method('deleteCalendar')
			->with($id);

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
