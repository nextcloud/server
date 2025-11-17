<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Security\Bruteforce\CleanupJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddBruteForceCleanupJob implements IRepairStep {
	public function __construct(
		protected IJobList $jobList,
	) {
	}

	public function getName() {
		return 'Add job to cleanup the bruteforce entries';
	}

	public function run(IOutput $output) {
		$this->jobList->add(CleanupJob::class);
	}
}
