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
namespace OCA\DAV\BackgroundJob;

use OC\BackgroundJob\TimedJob;
use OCA\DAV\CalDAV\Reminder\ReminderService;
use OCP\IConfig;

class EventReminderJob extends TimedJob {

	/** @var ReminderService */
	private $reminderService;

	/** @var IConfig */
	private $config;

	/**
	 * EventReminderJob constructor.
	 *
	 * @param ReminderService $reminderService
	 * @param IConfig $config
	 */
	public function __construct(ReminderService $reminderService, IConfig $config) {
		$this->reminderService = $reminderService;
		$this->config = $config;
		/** Run every 5 minutes */
		$this->setInterval(5);
	}

	/**
	 * @param $arg
	 * @throws \OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException
	 * @throws \OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException
	 * @throws \OC\User\NoUserException
	 */
	public function run($arg):void {
		if ($this->config->getAppValue('dav', 'sendEventReminders', 'yes') !== 'yes') {
			return;
		}

		if ($this->config->getAppValue('dav', 'sendEventRemindersMode', 'backgroundjob') !== 'backgroundjob') {
			return;
		}

		$this->reminderService->processReminders();
	}
}
