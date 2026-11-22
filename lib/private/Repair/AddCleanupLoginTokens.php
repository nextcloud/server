<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OC\User\BackgroundJobs\CleanupLoginTokens;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddCleanupLoginTokens implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Add cleanup login tokens background job';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$this->jobList->add(CleanupLoginTokens::class);
	}
}
