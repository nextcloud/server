<?php
/**
 * @copyright Copyright (c) 2017, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarHome;
use OCP\IConfig;
use Sabre\DAV\MkCol;
use Test\TestCase;

class GenerateBirthdayCalendarBackgroundJobTest extends TestCase {

	/** @var BirthdayService | \PHPUnit_Framework_MockObject_MockObject */
	private $birthdayService;

	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var \OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob */
	private $backgroundJob;

	protected function setUp() {
		parent::setUp();

		$this->birthdayService = $this->createMock(BirthdayService::class);
		$this->config = $this->createMock(IConfig::class);

		$this->backgroundJob = new GenerateBirthdayCalendarBackgroundJob(
			$this->birthdayService, $this->config);
	}

	public function testRun() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->will($this->returnValue('yes'));

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user123', 'dav', 'generateBirthdayCalendar', 'yes')
			->will($this->returnValue('yes'));

		$this->birthdayService->expects($this->once())
			->method('syncUser')
			->with('user123');

		$this->backgroundJob->run(['userId' => 'user123']);
	}

	public function testRunGloballyDisabled() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->will($this->returnValue('no'));

		$this->config->expects($this->never())
			->method('getUserValue');

		$this->birthdayService->expects($this->never())
			->method('syncUser');

		$this->backgroundJob->run(['userId' => 'user123']);
	}

	public function testRunUserDisabled() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->will($this->returnValue('yes'));

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user123', 'dav', 'generateBirthdayCalendar', 'yes')
			->will($this->returnValue('no'));

		$this->birthdayService->expects($this->never())
			->method('syncUser');

		$this->backgroundJob->run(['userId' => 'user123']);
	}
}
