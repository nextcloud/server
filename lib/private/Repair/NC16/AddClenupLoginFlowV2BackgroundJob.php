<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC16;

use OC\Core\BackgroundJobs\CleanupLoginFlowV2;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddClenupLoginFlowV2BackgroundJob implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function getName(): string {
		return 'Add background job to cleanup login flow v2 tokens';
	}

	public function run(IOutput $output) {
		$this->jobList->add(CleanupLoginFlowV2::class);
	}
}
