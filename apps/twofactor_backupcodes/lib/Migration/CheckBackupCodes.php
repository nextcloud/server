<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Migration;

use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CheckBackupCodes implements IRepairStep {

	public function __construct(
		private IJobList $jobList,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Add background job to check for backup codes';
	}

	#[\Override]
	public function run(IOutput $output) {
		$this->jobList->add(\OCA\TwoFactorBackupCodes\BackgroundJob\CheckBackupCodes::class);
	}
}
