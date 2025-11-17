<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\TaskProcessing\RemoveOldTasksBackgroundJob;
use OC\TextProcessing\RemoveOldTasksBackgroundJob as RemoveOldTextProcessingTasksBackgroundJob;
use OC\TextToImage\RemoveOldTasksBackgroundJob as RemoveOldTextToImageTasksBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddRemoveOldTasksBackgroundJob implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Add AI tasks cleanup jobs';
	}

	public function run(IOutput $output) {
		$this->jobList->add(RemoveOldTextProcessingTasksBackgroundJob::class);
		$this->jobList->add(RemoveOldTextToImageTasksBackgroundJob::class);
		$this->jobList->add(RemoveOldTasksBackgroundJob::class);
	}
}
