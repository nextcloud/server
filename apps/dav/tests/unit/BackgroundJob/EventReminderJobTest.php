<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\EventReminderJob;
use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class EventReminderJobTest extends TestCase {
	private ITimeFactory&MockObject $time;
	private ReminderService&MockObject $reminderService;
	private IConfig&MockObject $config;
	private EventReminderJob $backgroundJob;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->reminderService = $this->createMock(ReminderService::class);
		$this->config = $this->createMock(IConfig::class);

		$this->backgroundJob = new EventReminderJob(
			$this->time,
			$this->reminderService,
			$this->config,
		);
	}

	public static function data(): array {
		return [
			[true, true, true],
			[true, false, false],
			[false, true, false],
			[false, false, false],
		];
	}

	/**
	 *
	 * @param bool $sendEventReminders
	 * @param bool $sendEventRemindersMode
	 * @param bool $expectCall
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'data')]
	public function testRun(bool $sendEventReminders, bool $sendEventRemindersMode, bool $expectCall): void {
		$this->config->expects($this->exactly($sendEventReminders ? 2 : 1))
			->method('getAppValue')
			->willReturnMap([
				['dav', 'sendEventReminders', 'yes', ($sendEventReminders ? 'yes' : 'no')],
				['dav', 'sendEventRemindersMode', 'backgroundjob', ($sendEventRemindersMode ? 'backgroundjob' : 'cron')],
			]);

		if ($expectCall) {
			$this->reminderService->expects($this->once())
				->method('processReminders');
		} else {
			$this->reminderService->expects($this->never())
				->method('processReminders');
		}

		$this->backgroundJob->run([]);
	}
}
