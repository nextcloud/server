<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class CleanupDirectLinksJob extends TimedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private DirectMapper $mapper,
	) {
		parent::__construct($timeFactory);

		// Run once a day at off-peak time
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		// Delete all shares expired 24 hours ago
		$this->mapper->deleteExpired($this->time->getTime() - 60 * 60 * 24);
	}
}
