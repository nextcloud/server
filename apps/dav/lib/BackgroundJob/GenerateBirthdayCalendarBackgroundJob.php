<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\BirthdayService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IConfig;

class GenerateBirthdayCalendarBackgroundJob extends QueuedJob {

	public function __construct(
		ITimeFactory $time,
		private BirthdayService $birthdayService,
		private IConfig $config,
	) {
		parent::__construct($time);
	}

	public function run($argument) {
		$userId = $argument['userId'];
		$purgeBeforeGenerating = $argument['purgeBeforeGenerating'] ?? false;

		// make sure admin didn't change their mind
		$isGloballyEnabled = $this->config->getAppValue('dav', 'generateBirthdayCalendar', 'yes');
		if ($isGloballyEnabled !== 'yes') {
			return;
		}

		// did the user opt out?
		$isUserEnabled = $this->config->getUserValue($userId, 'dav', 'generateBirthdayCalendar', 'yes');
		if ($isUserEnabled !== 'yes') {
			return;
		}

		if ($purgeBeforeGenerating) {
			$this->birthdayService->resetForUser($userId);
		}

		$this->birthdayService->syncUser($userId);
	}
}
