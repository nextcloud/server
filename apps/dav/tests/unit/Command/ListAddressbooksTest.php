<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Command;

use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Command\ListAddressbooks;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class ListCalendarsTest
 *
 * @package OCA\DAV\Tests\Command
 */
class ListAddressbooksTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private CardDavBackend&MockObject $cardDavBackend;
	private ListAddressbooks $command;

	public const USERNAME = 'username';

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->cardDavBackend = $this->createMock(CardDavBackend::class);

		$this->command = new ListAddressbooks(
			$this->userManager,
			$this->cardDavBackend
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

		$this->cardDavBackend->expects($this->once())
			->method('getAddressBooksForUser')
			->with('principals/users/' . self::USERNAME)
			->willReturn([]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'uid' => self::USERNAME,
		]);
		$this->assertStringContainsString('User <' . self::USERNAME . "> has no addressbooks\n", $commandTester->getDisplay());
	}

	public static function dataExecute(): array {
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

		$this->cardDavBackend->expects($this->once())
			->method('getAddressBooksForUser')
			->with('principals/users/' . self::USERNAME)
			->willReturn([
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
	}
}
