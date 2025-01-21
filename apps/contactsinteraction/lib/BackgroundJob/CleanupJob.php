<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\BackgroundJob;

use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class CleanupJob extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		private RecentContactMapper $mapper,
	) {
		parent::__construct($time);

		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);

	}

	protected function run(mixed $argument): void {
		$time = $this->time->getDateTime();
		$time->modify('-7days');
		$this->mapper->cleanUp($time->getTimestamp());
	}
}
