<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author François Freitag <mail@franek.fr>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Tests\Unit\DAV\Controller;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Controller\BirthdayCalendarController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BirthdayCalendarControllerTest extends TestCase {

	/** @var IConfig|MockObject */
	private $config;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var CalDavBackend|MockObject */
	private $caldav;

	/** @var BirthdayCalendarController|MockObject */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$request = $this->createMock(IRequest::class);
		$db = $this->createMock(IDBConnection::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->caldav = $this->createMock(CalDavBackend::class);

		$this->controller = new BirthdayCalendarController('dav',
			$request, $db, $this->config, $this->jobList,
			$this->userManager, $this->caldav);
	}

	public function testEnable() {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes');

		$this->userManager->expects($this->once())
			->method('callForSeenUsers')
			->willReturnCallback(function ($closure) {
				$user1 = $this->createMock(IUser::class);
				$user1->method('getUID')->willReturn('uid1');
				$user2 = $this->createMock(IUser::class);
				$user2->method('getUID')->willReturn('uid2');
				$user3 = $this->createMock(IUser::class);
				$user3->method('getUID')->willReturn('uid3');

				$closure($user1);
				$closure($user2);
				$closure($user3);
			});

		$this->jobList->expects($this->exactly(3))
			->method('add')
			->withConsecutive(
				[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid1']],
				[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid2']],
				[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid3']],
			);

		$response = $this->controller->enable();
		$this->assertInstanceOf(JSONResponse::class, $response);
	}

	public function testDisable() {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('dav', 'generateBirthdayCalendar', 'no');
		$this->jobList->expects($this->once())
			->method('remove')
			->with(GenerateBirthdayCalendarBackgroundJob::class);
		$this->caldav->expects($this->once())
			->method('deleteAllBirthdayCalendars');

		$response = $this->controller->disable();
		$this->assertInstanceOf(JSONResponse::class, $response);
	}
}
