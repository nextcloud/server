<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OC\Core\BackgroundJobs\UserMigrationJob;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Override;

class AddMoveUsersJob implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
		private readonly IAppConfig $appConfig,
	) {
	}

	#[Override]
	public function getName(): string {
		return 'Queue one-time jobs to move the users';
	}

	#[Override]
	public function run(IOutput $output): void {
		$nbWorkers = 10;
		for ($i = 0; $i < $nbWorkers; ++$i) {
			$this->jobList->add(UserMigrationJob::class, ['workerId' => $i, 'nbWorkers' => $nbWorkers]);
		}
	}
}
