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

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject $userManager */
	private $userManager;

	/** @var CalDavBackend|\PHPUnit_Framework_MockObject_MockObject $l10n */
	private $calDav;

	/** @var ListCalendars */
	private $command;

	const USERNAME = 'username';

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->calDav = $this->createMock(CalDavBackend::class);

		$this->command = new ListCalendars(
			$this->userManager,
			$this->calDav
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testWithBadUser()
	{
		$this->userManager->expects($this->once())
			->method('userExists')
			->with(self::USERNAME)
			->willReturn(false);

		$commandTester = new CommandTester($this->command);
		$commandTester->execute([
			'user' => self::USERNAME,
		]);
		$this->assertContains("User <" . self::USERNAME . "> in unknown", $commandTester->getDisplay());
	}

	public function testWithCorrectUserWithNoCalendars()
	{
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
			'user' => self::USERNAME,
		]);
		$this->assertContains("User <" . self::USERNAME . "> has no calendars\n", $commandTester->getDisplay());
	}

	public function dataExecute()
	{
		return [
			[false, '✓'],
			[true, 'x']
		];
	}

	/**
	 * @dataProvider dataExecute
	 */
	public function testWithCorrectUser(bool $readOnly, string $output)
	{
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
			'user' => self::USERNAME,
		]);
		$this->assertContains($output, $commandTester->getDisplay());
		$this->assertNotContains(BirthdayService::BIRTHDAY_CALENDAR_URI, $commandTester->getDisplay());
	}
}
