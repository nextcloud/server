<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException;
use OCA\DAV\CalDAV\Reminder\NotificationTypeDoesNotExistException;
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
	 * @throws ProviderNotAvailableException
	 * @throws NotificationTypeDoesNotExistException
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
