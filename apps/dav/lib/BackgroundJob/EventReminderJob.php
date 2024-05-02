<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

class EventReminderJob extends TimedJob {

	/** @var ReminderService */
	private $reminderService;

	/** @var IConfig */
	private $config;

	public function __construct(ITimeFactory $time,
		ReminderService $reminderService,
		IConfig $config) {
		parent::__construct($time);
		$this->reminderService = $reminderService;
		$this->config = $config;

		// Run every 5 minutes
		$this->setInterval(5 * 60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	/**
	 * @throws \OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException
	 * @throws \OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException
	 * @throws \OC\User\NoUserException
	 */
	public function run($argument):void {
		if ($this->config->getAppValue('dav', 'sendEventReminders', 'yes') !== 'yes') {
			return;
		}

		if ($this->config->getAppValue('dav', 'sendEventRemindersMode', 'backgroundjob') !== 'backgroundjob') {
			return;
		}

		$this->reminderService->processReminders();
	}
}
