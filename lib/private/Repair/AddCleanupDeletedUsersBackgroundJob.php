<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\User\BackgroundJobs\CleanupDeletedUsers;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddCleanupDeletedUsersBackgroundJob implements IRepairStep {
	private IJobList $jobList;

	public function __construct(IJobList $jobList) {
		$this->jobList = $jobList;
	}

	public function getName(): string {
		return 'Add cleanup-deleted-users background job';
	}

	public function run(IOutput $output) {
		$this->jobList->add(CleanupDeletedUsers::class);
	}
}
