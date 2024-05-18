<?php

declare(strict_types=1);

/**
 * @copyright 2018, Thomas Citharel <tcit@tcit.fr>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\EventReminderJob;
use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class EventReminderJobTest extends TestCase {
	/** @var ITimeFactory|MockObject */
	private $time;

	/** @var ReminderService|MockObject */
	private $reminderService;

	/** @var IConfig|MockObject */
	private $config;

	/** @var EventReminderJob|MockObject */
	private $backgroundJob;

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

	public function data(): array {
		return [
			[true, true, true],
			[true, false, false],
			[false, true, false],
			[false, false, false],
		];
	}

	/**
	 * @dataProvider data
	 *
	 * @param bool $sendEventReminders
	 * @param bool $sendEventRemindersMode
	 * @param bool $expectCall
	 */
	public function testRun(bool $sendEventReminders, bool $sendEventRemindersMode, bool $expectCall): void {
		$this->config->expects($this->exactly($sendEventReminders ? 2 : 1))
			->method('getAppValue')
			->withConsecutive(
				['dav', 'sendEventReminders', 'yes'],
				['dav', 'sendEventRemindersMode', 'backgroundjob'],
			)
			->willReturnOnConsecutiveCalls(
				$sendEventReminders ? 'yes' : 'no',
				$sendEventRemindersMode ? 'backgroundjob' : 'cron'
			);

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
