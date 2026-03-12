<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Core\BackgroundJobs\PreviewMigrationJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Override;

class AddMovePreviewJob implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Queue a job to move the preview';
	}

	#[Override]
	public function run(IOutput $output): void {
		$this->jobList->add(PreviewMigrationJob::class);
	}
}
