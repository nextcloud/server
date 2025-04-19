<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC32;

use OC\Core\BackgroundJobs\FileCacheGcJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddFileCacheGcBackgroundJob implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Add background job to cleanup file cache';
	}

	public function run(IOutput $output) {
		$this->jobList->add(FileCacheGcJob::class);
	}
}
