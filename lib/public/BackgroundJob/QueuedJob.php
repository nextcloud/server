<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\BackgroundJob;

/**
 * Simple base class for a one time background job
 *
 * @since 15.0.0
 * @since 25.0.0 deprecated `execute()` method in favor of `start()`
 * @since 33.0.0 removed deprecated `execute()` method
 */
abstract class QueuedJob extends Job {

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
