<?php

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Core\BackgroundJobs\MovePreviewJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddMovePreviewJob implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Queue a job to move the preview';
	}

	public function run(IOutput $output): void {
		$this->jobList->add(MovePreviewJob::class);
	}
}
