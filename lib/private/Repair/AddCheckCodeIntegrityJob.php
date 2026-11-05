<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OC\Core\BackgroundJobs\CheckCodeIntegrityJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Override;

class AddCheckCodeIntegrityJob implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Add code integrity check background job';
	}

	#[Override]
	public function run(IOutput $output): void {
		$this->jobList->add(CheckCodeIntegrityJob::class);
	}
}
