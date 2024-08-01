<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OC\User\OutOfOfficeData;
use OCA\DAV\BackgroundJob\UserStatusAutomation;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\User\IAvailabilityCoordinator;
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
	private IAvailabilityCoordinator|MockObject $coordinator;
	private IUserManager|MockObject $userManager;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->statusManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$this->userManager = $this->createMock(IUserManager::class);

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
				$this->coordinator,
				$this->userManager,
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
				$this->coordinator,
				$this->userManager,
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
	public function testRunNoOOO(string $ruleDay, string $currentTime, bool $isAvailable): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'user'
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->coordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->config->method('getUserValue')
			->with('user', 'dav', 'user_status_automation', 'no')
			->willReturn('yes');
		$this->time->method('getDateTime')
			->willReturn(new \DateTime($currentTime, new \DateTimeZone('UTC')));
		$this->logger->expects(self::exactly(4))
			->method('debug');
		if (!$isAvailable) {
			$this->statusManager->expects(self::once())
				->method('setUserStatus')
				->with('user', IUserStatus::MESSAGE_AVAILABILITY, IUserStatus::DND, true);
		}
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

		self::invokePrivate($automation, 'run', [['userId' => 'user']]);
	}

	public function testRunNoAvailabilityNoOOO(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'user'
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->coordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn(null);
		$this->config->method('getUserValue')
			->with('user', 'dav', 'user_status_automation', 'no')
			->willReturn('yes');
		$this->time->method('getDateTime')
			->willReturn(new \DateTime('2023-02-24 13:58:24.479357', new \DateTimeZone('UTC')));
		$this->jobList->expects($this->once())
			->method('remove')
			->with(UserStatusAutomation::class, ['userId' => 'user']);
		$this->logger->expects(self::once())
			->method('debug');
		$this->logger->expects(self::once())
			->method('info');
		$automation = $this->getAutomationMock(['getAvailabilityFromPropertiesTable']);
		$automation->method('getAvailabilityFromPropertiesTable')
			->with('user')
			->willReturn(false);

		self::invokePrivate($automation, 'run', [['userId' => 'user']]);
	}

	public function testRunNoAvailabilityWithOOO(): void {
		$user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'user'
		]);
		$ooo = $this->createConfiguredMock(OutOfOfficeData::class, [
			'getShortMessage' => 'On Vacation',
			'getEndDate' => 123456,
		]);

		$this->userManager->expects(self::once())
			->method('get')
			->willReturn($user);
		$this->coordinator->expects(self::once())
			->method('getCurrentOutOfOfficeData')
			->willReturn($ooo);
		$this->coordinator->expects(self::once())
			->method('isInEffect')
			->willReturn(true);
		$this->statusManager->expects(self::once())
			->method('setUserStatus')
			->with('user', IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::DND, true, $ooo->getShortMessage());
		$this->config->expects(self::never())
			->method('getUserValue');
		$this->time->method('getDateTime')
			->willReturn(new \DateTime('2023-02-24 13:58:24.479357', new \DateTimeZone('UTC')));
		$this->jobList->expects($this->never())
			->method('remove');
		$this->logger->expects(self::exactly(2))
			->method('debug');
		$automation = $this->getAutomationMock([]);

		self::invokePrivate($automation, 'run', [['userId' => 'user']]);
	}
}
