<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\BackgroundJob;

use OCP\ILogger;

/**
 * Simple base class for a one time background job
 *
 * @since 15.0.0
 */
abstract class QueuedJob extends Job {
	/**
	 * Run the job, then remove it from the joblist
	 *
	 * @param IJobList $jobList
	 * @param ILogger|null $logger
	 *
	 * @since 15.0.0
	 * @deprecated 25.0.0 Use start() instead. This method will be removed
	 * with the ILogger interface
	 */
	final public function execute($jobList, ?ILogger $logger = null) {
		$this->start($jobList);
	}

	/**
	 * Run the job, then remove it from the joblist
	 *
	 * @since 25.0.0
	 */
	final public function start(IJobList $jobList): void {
		if ($this->id) {
			$jobList->removeById($this->id);
		} else {
			$jobList->remove($this, $this->argument);
		}
		parent::start($jobList);
	}
}
