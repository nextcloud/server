<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\Command;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Command\ListCalendars;
use OCP\IUserManager;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class ListCalendarsTest
 *
 * @package OCA\DAV\Tests\Command
 */
class ListCalendarsTest extends TestCase {

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject $userManager */
	private $userManager;

	/** @var CalDavBackend|\PHPUnit\Framework\MockObject\MockObject $l10n */
	private $calDav;

	/** @var ListCalendars */
	private $command;

	public const USERNAME = 'username';

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->calDav = $this->createMock(CalDavBackend::class);

		$this->command = new ListCalendars(
			$this->userManager,
			$this->calDav
		);
	}

	public function testWithBadUser(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USERNAME)
			->willReturn(false);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USERNAME,
		]);
		$this->assertStringContainsString('User <' . self::USERNAME . '> in unknown', $commandTester->getDisplay());
	}

	public function testWithCorrectUserWithNoCalendars(): void {
		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USERNAME)
			->willReturn(true);

		$this->calDav->expects($this->once())
			->method('getCalendarsForUser')
			->with('principals/users/' . self::USERNAME)
			->willReturn([]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USERNAME,
		]);
		$this->assertStringContainsString('User <' . self::USERNAME . "> has no calendars\n", $commandTester->getDisplay());
	}

	public function dataExecute() {
		return [
			[false, '✓'],
			[true, 'x']
		];
	}

	/**
	 * @dataProvider dataExecute
	 */
	public function testWithCorrectUser(bool $readOnly, string $output): void {
		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USERNAME)
			->willReturn(true);

		$this->calDav->expects($this->once())
			->method('getCalendarsForUser')
			->with('principals/users/' . self::USERNAME)
			->willReturn([
				[
					'uri' => BirthdayService::BIRTHDAY_CALENDAR_URI,
				],
				[
					'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only' => $readOnly,
					'uri' => 'test',
					'{DAV:}displayname' => 'dp',
					'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => 'owner-principal',
					'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}owner-displayname' => 'owner-dp',
				]
			]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USERNAME,
		]);
		$this->assertStringContainsString($output, $commandTester->getDisplay());
		$this->assertStringNotContainsString(BirthdayService::BIRTHDAY_CALENDAR_URI, $commandTester->getDisplay());
	}
}
