<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\TaskProcessing;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class RemoveOldTasksBackgroundJob extends TimedJob {

	public function __construct(
		ITimeFactory $timeFactory,
		private Manager $taskProcessingManager,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(60 * 60 * 24);
		// can be deferred to maintenance window
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @inheritDoc
	 */
	protected function run($argument): void {
		iterator_to_array($this->taskProcessingManager->cleanupOldTasks());
	}
}
