<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC14;

use OC\Preview\BackgroundCleanupJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddPreviewBackgroundCleanupJob implements IRepairStep {
	/** @var IJobList */
	private $jobList;

	public function __construct(IJobList $jobList) {
		$this->jobList = $jobList;
	}

	public function getName(): string {
		return 'Add preview background cleanup job';
	}

	public function run(IOutput $output) {
		$this->jobList->add(BackgroundCleanupJob::class);
	}
}
