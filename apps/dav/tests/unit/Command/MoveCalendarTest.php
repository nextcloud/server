<?php
/**
 * @copyright Copyright (c) 2016 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\Tests\Command;

use InvalidArgumentException;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Command\MoveCalendar;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class MoveCalendarTest
 *
 * @package OCA\DAV\Tests\Command
 */
class MoveCalendarTest extends TestCase {
	/** @var \OCP\IUserManager|MockObject $userManager */
	private $userManager;

	/** @var \OCP\IGroupManager|MockObject $groupManager */
	private $groupManager;

	/** @var \OCP\Share\IManager|MockObject $shareManager */
	private $shareManager;

	/** @var IConfig|MockObject $l10n */
	private $config;

	/** @var IL10N|MockObject $l10n */
	private $l10n;

	/** @var CalDavBackend|MockObject $l10n */
	private $calDav;

	/** @var MoveCalendar */
	private $command;

	/** @var LoggerInterface|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->calDav = $this->createMock(CalDavBackend::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->command = new MoveCalendar(
			$this->userManager,
			$this->groupManager,
			$this->shareManager,
			$this->config,
			$this->l10n,
			$this->calDav,
			$this->logger
		);
	}

	public function dataExecute() {
		return [
			[false, true],
			[true, false]
		];
	}

	/**
	 * @dataProvider dataExecute
	 *
	 * @param $userOriginExists
	 * @param $userDestinationExists
	 */
	public function testWithBadUserOrigin($userOriginExists, $userDestinationExists): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->userManager->expects($this->exactly($userOriginExists ? 2 : 1))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturnOnConsecutiveCalls(
				$userOriginExists,
				$userDestinationExists,
			);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => $this->command->getName(),
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
		]);
	}


	public function testMoveWithInexistantCalendar(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('User <user> has no calendar named <personal>. You can run occ dav:list-calendars to list calendars URIs for this user.');

		$this->userManager->expects($this->exactly(2))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturn(true);

		$this->calDav->expects($this->once())->method('getCalendarByUri')
			->with('principals/users/user', 'personal')
			->willReturn(null);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
		]);
	}


	public function testMoveWithExistingDestinationCalendar(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('User <user2> already has a calendar named <personal>.');

		$this->userManager->expects($this->exactly(2))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturn(true);

		$this->calDav->expects($this->exactly(2))
			->method('getCalendarByUri')
			->withConsecutive(
				['principals/users/user', 'personal'],
				['principals/users/user2', 'personal'],
			)
			->willReturn([
				'id' => 1234,
			]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
		]);
	}

	public function testMove(): void {
		$this->userManager->expects($this->exactly(2))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturn(true);

		$this->calDav->expects($this->exactly(2))
			->method('getCalendarByUri')
			->withConsecutive(
				['principals/users/user', 'personal'],
				['principals/users/user2', 'personal'],
			)
			->willReturnOnConsecutiveCalls(
				[
					'id' => 1234,
				],
				null,
			);

		$this->calDav->expects($this->once())->method('getShares')
			->with(1234)
			->willReturn([]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
		]);

		$this->assertStringContainsString("[OK] Calendar <personal> was moved from user <user> to <user2>", $commandTester->getDisplay());
	}

	public function dataTestMoveWithDestinationNotPartOfGroup(): array {
		return [
			[true],
			[false]
		];
	}

	/**
	 * @dataProvider dataTestMoveWithDestinationNotPartOfGroup
	 */
	public function testMoveWithDestinationNotPartOfGroup(bool $shareWithGroupMembersOnly): void {
		$this->userManager->expects($this->exactly(2))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturn(true);

		$this->calDav->expects($this->exactly(2))
			->method('getCalendarByUri')
			->withConsecutive(
				['principals/users/user', 'personal'],
				['principals/users/user2', 'personal'],
			)
			->willReturnOnConsecutiveCalls(
				[
					'id' => 1234,
					'uri' => 'personal',
				],
				null,
			);

		$this->shareManager->expects($this->once())->method('shareWithGroupMembersOnly')
			->willReturn($shareWithGroupMembersOnly);

		$this->calDav->expects($this->once())->method('getShares')
			->with(1234)
			->willReturn([
				['href' => 'principal:principals/groups/nextclouders']
			]);
		if ($shareWithGroupMembersOnly === true) {
			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessage("User <user2> is not part of the group <nextclouders> with whom the calendar <personal> was shared. You may use -f to move the calendar while deleting this share.");
		}

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
		]);
	}

	public function testMoveWithDestinationPartOfGroup(): void {
		$this->userManager->expects($this->exactly(2))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturn(true);

		$this->calDav->expects($this->exactly(2))
			->method('getCalendarByUri')
			->withConsecutive(
				['principals/users/user', 'personal'],
				['principals/users/user2', 'personal'],
			)
			->willReturnOnConsecutiveCalls(
				[
					'id' => 1234,
					'uri' => 'personal',
				],
				null,
			);

		$this->shareManager->expects($this->once())->method('shareWithGroupMembersOnly')
			->willReturn(true);

		$this->calDav->expects($this->once())->method('getShares')
			->with(1234)
			->willReturn([
				['href' => 'principal:principals/groups/nextclouders']
			]);

		$this->groupManager->expects($this->once())->method('isInGroup')
			->with('user2', 'nextclouders')
			->willReturn(true);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
		]);

		$this->assertStringContainsString("[OK] Calendar <personal> was moved from user <user> to <user2>", $commandTester->getDisplay());
	}

	public function testMoveWithDestinationNotPartOfGroupAndForce(): void {
		$this->userManager->expects($this->exactly(2))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturn(true);

		$this->calDav->expects($this->exactly(2))
			->method('getCalendarByUri')
			->withConsecutive(
				['principals/users/user', 'personal'],
				['principals/users/user2', 'personal'],
			)
			->willReturnOnConsecutiveCalls(
				[
					'id' => 1234,
					'uri' => 'personal',
					'{DAV:}displayname' => 'Personal'
				],
				null,
			);

		$this->shareManager->expects($this->once())->method('shareWithGroupMembersOnly')
			->willReturn(true);

		$this->calDav->expects($this->once())->method('getShares')
			->with(1234)
			->willReturn([
				[
					'href' => 'principal:principals/groups/nextclouders',
					'{DAV:}displayname' => 'Personal'
				]
			]);
		$this->calDav->expects($this->once())->method('updateShares');

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
			'--force' => true
		]);

		$this->assertStringContainsString("[OK] Calendar <personal> was moved from user <user> to <user2>", $commandTester->getDisplay());
	}

	public function dataTestMoveWithCalendarAlreadySharedToDestination(): array {
		return [
			[true],
			[false]
		];
	}

	/**
	 * @dataProvider dataTestMoveWithCalendarAlreadySharedToDestination
	 */
	public function testMoveWithCalendarAlreadySharedToDestination(bool $force): void {
		$this->userManager->expects($this->exactly(2))
			->method('userExists')
			->withConsecutive(
				['user'],
				['user2'],
			)
			->willReturn(true);

		$this->calDav->expects($this->exactly(2))
			->method('getCalendarByUri')
			->withConsecutive(
				['principals/users/user', 'personal'],
				['principals/users/user2', 'personal'],
			)
			->willReturnOnConsecutiveCalls(
				[
					'id' => 1234,
					'uri' => 'personal',
					'{DAV:}displayname' => 'Personal'
				],
				null,
			);

		$this->calDav->expects($this->once())->method('getShares')
				->with(1234)
				->willReturn([
					[
						'href' => 'principal:principals/users/user2',
						'{DAV:}displayname' => 'Personal'
					]
				]);

		if ($force === false) {
			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessage("The calendar <personal> is already shared to user <user2>.You may use -f to move the calendar while deleting this share.");
		} else {
			$this->calDav->expects($this->once())->method('updateShares');
		}

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'sourceuid' => 'user',
			'destinationuid' => 'user2',
			'--force' => $force,
		]);
	}
}
