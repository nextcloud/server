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
use OCA\DAV\Command\MoveCalendar;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;


/**
 * Class MoveCalendarTest
 *
 * @package OCA\DAV\Tests\Command
 * @group DB
 */
class MoveCalendarTest extends TestCase {

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject $userManager */
	private $userManager;

	/** @var \OCP\IGroupManager|\PHPUnit_Framework_MockObject_MockObject $groupManager */
	private $groupManager;

	/** @var \OCP\IDBConnection|\PHPUnit_Framework_MockObject_MockObject $dbConnection */
	private $dbConnection;

	/** @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject $l10n */
	private $l10n;

	/** @var MoveCalendar */
	private $command;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->command = new MoveCalendar(
			$this->userManager,
			$this->groupManager,
			$this->dbConnection,
			$this->l10n
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
}