<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\UserStatusAutomation;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\UserStatus\IManager;
use OCP\UserStatus\IUserStatus;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * @group DB
 */
class UserStatusAutomationTest extends TestCase {

	protected MockObject|ITimeFactory $time;
	protected MockObject|IJobList $jobList;
	protected MockObject|LoggerInterface $logger;
	protected MockObject|IManager $statusManager;
	protected MockObject|IConfig $config;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->statusManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);

	}

	protected function getAutomationMock(array $methods): MockObject|UserStatusAutomation {
		if (empty($methods)) {
			return new UserStatusAutomation(
				$this->time,
				\OC::$server->getDatabaseConnection(),
				$this->jobList,
				$this->logger,
				$this->statusManager,
				$this->config,
			);
		}

		return $this->getMockBuilder(UserStatusAutomation::class)
			->setConstructorArgs([
				$this->time,
				\OC::$server->getDatabaseConnection(),
				$this->jobList,
				$this->logger,
				$this->statusManager,
				$this->config,
			])
			->setMethods($methods)
			->getMock();
	}

	public function dataRun(): array {
		return [
			['20230217', '2023-02-24 10:49:36.613834', true],
			['20230224', '2023-02-24 10:49:36.613834', true],
			['20230217', '2023-02-24 13:58:24.479357', false],
			['20230224', '2023-02-24 13:58:24.479357', false],
		];
	}

	/**
	 * @dataProvider dataRun
	 */
	public function testRun(string $ruleDay, string $currentTime, bool $isAvailable): void {
		$this->config->method('getUserValue')
			->with('user', 'dav', 'user_status_automation', 'no')
			->willReturn('yes');

		$this->time->method('getDateTime')
			->willReturn(new \DateTime($currentTime, new \DateTimeZone('UTC')));

		$automation = $this->getAutomationMock(['getAvailabilityFromPropertiesTable']);
		$automation->method('getAvailabilityFromPropertiesTable')
			->with('user')
			->willReturn('BEGIN:VCALENDAR
PRODID:Nextcloud DAV app
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:STANDARD
TZNAME:CET
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZNAME:CEST
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VAVAILABILITY
BEGIN:AVAILABLE
DTSTART;TZID=Europe/Berlin:' . $ruleDay . 'T090000
DTEND;TZID=Europe/Berlin:' . $ruleDay . 'T170000
UID:3e6feeec-8e00-4265-b822-b73174e8b39f
RRULE:FREQ=WEEKLY;BYDAY=TH
END:AVAILABLE
BEGIN:AVAILABLE
DTSTART;TZID=Europe/Berlin:' . $ruleDay . 'T090000
DTEND;TZID=Europe/Berlin:' . $ruleDay . 'T120000
UID:8a634e99-07cf-443b-b480-005a0e1db323
RRULE:FREQ=WEEKLY;BYDAY=FR
END:AVAILABLE
END:VAVAILABILITY
END:VCALENDAR');

		if ($isAvailable) {
			$this->statusManager->expects($this->once())
				->method('revertUserStatus')
				->with('user', IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);
		} else {
			$this->statusManager->expects($this->once())
				->method('revertUserStatus')
				->with('user', IUserStatus::MESSAGE_CALL, IUserStatus::AWAY);
			$this->statusManager->expects($this->once())
				->method('setUserStatus')
				->with('user', IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND, true);
		}

		self::invokePrivate($automation, 'run', [['userId' => 'user']]);
	}

	public function testRunNoMoreAvailabilityDefined(): void {
		$this->config->method('getUserValue')
			->with('user', 'dav', 'user_status_automation', 'no')
			->willReturn('yes');

		$this->time->method('getDateTime')
			->willReturn(new \DateTime('2023-02-24 13:58:24.479357', new \DateTimeZone('UTC')));

		$automation = $this->getAutomationMock(['getAvailabilityFromPropertiesTable']);
		$automation->method('getAvailabilityFromPropertiesTable')
			->with('user')
			->willReturn('BEGIN:VCALENDAR
PRODID:Nextcloud DAV app
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:STANDARD
TZNAME:CET
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
BEGIN:DAYLIGHT
TZNAME:CEST
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
END:VTIMEZONE
BEGIN:VAVAILABILITY
END:VAVAILABILITY
END:VCALENDAR');

		$this->statusManager->expects($this->once())
			->method('revertUserStatus')
			->with('user', IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND);

		$this->jobList->expects($this->once())
			->method('remove')
			->with(UserStatusAutomation::class, ['userId' => 'user']);

		self::invokePrivate($automation, 'run', [['userId' => 'user']]);
	}
}
