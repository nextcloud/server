<?php
/**
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
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
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;


/**
 * Class MoveCalendarTest
 *
 * @package OCA\DAV\Tests\Command
 */
class MoveCalendarTest extends TestCase {

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject $userManager */
	private $userManager;

	/** @var \OCP\IGroupManager|\PHPUnit_Framework_MockObject_MockObject $groupManager */
	private $groupManager;

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject $l10n */
	private $config;

	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject $l10n */
	private $l10n;

	/** @var CalDavBackend|\PHPUnit_Framework_MockObject_MockObject $l10n */
	private $calDav;

	/** @var MoveCalendar */
	private $command;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->calDav = $this->createMock(CalDavBackend::class);

		$this->command = new MoveCalendar(
			$this->userManager,
			$this->groupManager,
			$this->config,
			$this->l10n,
			$this->calDav
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
	 * @expectedException InvalidArgumentException
	 * @param $userOriginExists
	 * @param $userDestinationExists
	 */
	public function testWithBadUserOrigin($userOriginExists, $userDestinationExists)
	{
		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with('user')
			->willReturn($userOriginExists);

		if (!$userDestinationExists) {
			$this->userManager->expects($this->at(1))
				->method('userExists')
				->with('user2')
				->willReturn($userDestinationExists);
		}

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => $this->command->getName(),
			'userorigin' => 'user',
			'userdestination' => 'user2',
		]);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage User can't be system
	 */
	public function testTryToMoveToOrFromSystem()
	{
		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => $this->command->getName(),
			'userorigin' => 'system',
			'userdestination' => 'user2',
		]);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage User <user> has no calendar named <personal>. You can run occ dav:list-calendars to list calendars URIs for this user.
	 */
	public function testMoveWithInexistantCalendar()
	{
		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with('user')
			->willReturn(true);

		$this->userManager->expects($this->at(1))
			->method('userExists')
			->with('user2')
			->willReturn(true);

		$this->calDav->expects($this->once())->method('getCalendarByUri')
			->with('principals/users/user', 'personal')
			->willReturn(null);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'userorigin' => 'user',
			'userdestination' => 'user2',
		]);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage User <user2> already has a calendar named <personal>.
	 */
	public function testMoveWithExistingDestinationCalendar()
	{
		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with('user')
			->willReturn(true);

		$this->userManager->expects($this->at(1))
			->method('userExists')
			->with('user2')
			->willReturn(true);

		$this->calDav->expects($this->at(0))->method('getCalendarByUri')
			->with('principals/users/user', 'personal')
			->willReturn([
				'id' => 1234,
			]);

			$this->calDav->expects($this->at(1))->method('getCalendarByUri')
			->with('principals/users/user2', 'personal')
			->willReturn([
				'id' => 1234,
			]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'userorigin' => 'user',
			'userdestination' => 'user2',
		]);
	}

	public function testMove()
	{
		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with('user')
			->willReturn(true);

		$this->userManager->expects($this->at(1))
			->method('userExists')
			->with('user2')
			->willReturn(true);

		$this->calDav->expects($this->at(0))->method('getCalendarByUri')
			->with('principals/users/user', 'personal')
			->willReturn([
				'id' => 1234,
			]);

		$this->calDav->expects($this->at(1))->method('getCalendarByUri')
			->with('principals/users/user2', 'personal')
			->willReturn(null);

		$this->calDav->expects($this->once())->method('getShares')
			->with(1234)
			->willReturn([]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'userorigin' => 'user',
			'userdestination' => 'user2',
		]);

		$this->assertContains("[OK] Calendar <personal> was moved from user <user> to <user2>", $commandTester->getDisplay());
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage User <user2> is not part of the group <nextclouders> with whom the calendar <personal> was shared. You may use -f to move the calendar while deleting this share.
	 */
	public function testMoveWithDestinationNotPartOfGroup()
	{
		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with('user')
			->willReturn(true);

		$this->userManager->expects($this->at(1))
			->method('userExists')
			->with('user2')
			->willReturn(true);

		$this->calDav->expects($this->at(0))->method('getCalendarByUri')
			->with('principals/users/user', 'personal')
			->willReturn([
				'id' => 1234,
				'uri' => 'personal'
			]);

		$this->calDav->expects($this->at(1))->method('getCalendarByUri')
			->with('principals/users/user2', 'personal')
			->willReturn(null);

		$this->calDav->expects($this->once())->method('getShares')
			->with(1234)
			->willReturn([
				['href' => 'principal:principals/groups/nextclouders']
			]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'userorigin' => 'user',
			'userdestination' => 'user2',
		]);
	}

	public function testMoveWithDestinationPartOfGroup()
	{
		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with('user')
			->willReturn(true);

		$this->userManager->expects($this->at(1))
			->method('userExists')
			->with('user2')
			->willReturn(true);

		$this->calDav->expects($this->at(0))->method('getCalendarByUri')
			->with('principals/users/user', 'personal')
			->willReturn([
				'id' => 1234,
				'uri' => 'personal'
			]);

		$this->calDav->expects($this->at(1))->method('getCalendarByUri')
			->with('principals/users/user2', 'personal')
			->willReturn(null);

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
			'userorigin' => 'user',
			'userdestination' => 'user2',
		]);

		$this->assertContains("[OK] Calendar <personal> was moved from user <user> to <user2>", $commandTester->getDisplay());
	}

	public function testMoveWithDestinationNotPartOfGroupAndForce()
	{
		$this->userManager->expects($this->at(0))
			->method('userExists')
			->with('user')
			->willReturn(true);

		$this->userManager->expects($this->at(1))
			->method('userExists')
			->with('user2')
			->willReturn(true);

		$this->calDav->expects($this->at(0))->method('getCalendarByUri')
			->with('principals/users/user', 'personal')
			->willReturn([
				'id' => 1234,
				'uri' => 'personal',
				'{DAV:}displayname' => 'personal'
			]);

		$this->calDav->expects($this->at(1))->method('getCalendarByUri')
			->with('principals/users/user2', 'personal')
			->willReturn(null);

		$this->calDav->expects($this->once())->method('getShares')
			->with(1234)
			->willReturn([
				['href' => 'principal:principals/groups/nextclouders']
			]);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'name' => 'personal',
			'userorigin' => 'user',
			'userdestination' => 'user2',
			'--force' => true
		]);

		$this->assertContains("[OK] Calendar <personal> was moved from user <user> to <user2>", $commandTester->getDisplay());
	}
}
