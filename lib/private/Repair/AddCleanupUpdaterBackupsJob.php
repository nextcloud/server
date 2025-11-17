<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Core\BackgroundJobs\BackgroundCleanupUpdaterBackupsJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddCleanupUpdaterBackupsJob implements IRepairStep {
	public function __construct(
		protected IJobList $jobList,
	) {
	}

	public function getName() {
		return 'Queue a one-time job to cleanup old backups of the updater';
	}

	public function run(IOutput $output) {
		$this->jobList->add(BackgroundCleanupUpdaterBackupsJob::class);
	}
}
