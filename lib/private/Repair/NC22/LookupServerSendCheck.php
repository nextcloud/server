<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC22;

use OC\Core\BackgroundJobs\LookupServerSendCheckBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class LookupServerSendCheck implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
		private IConfig $config,
	) {
	}

	public function getName(): string {
		return 'Add background job to set the lookup server share state for users';
	}

	public function run(IOutput $output): void {
		$this->jobList->add(LookupServerSendCheckBackgroundJob::class);
	}
}
