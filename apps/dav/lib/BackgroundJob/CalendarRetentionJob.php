<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\RetentionService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class CalendarRetentionJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private RetentionService $service,
	) {
		parent::__construct($time);

		// Run four times a day
		$this->setInterval(6 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	protected function run($argument): void {
		$this->service->cleanUp();
	}
}
