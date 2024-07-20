<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\BackgroundJob;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class DeleteOutdatedSchedulingObjects extends TimedJob {
	public function __construct(
		private CalDavBackend $calDavBackend,
		private LoggerInterface $logger,
		ITimeFactory $timeFactory,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(23 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @param array $argument
	 */
	protected function run($argument): void {
		$time = $this->time->getTime() - (60 * 60);
		$this->calDavBackend->deleteOutdatedSchedulingObjects($time, 50000);
		$this->logger->info("Removed outdated scheduling objects");
	}
}
