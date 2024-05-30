<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC24;

use OC\Authentication\Token\TokenCleanupJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddTokenCleanupJob implements IRepairStep {
	private IJobList $jobList;

	public function __construct(IJobList $jobList) {
		$this->jobList = $jobList;
	}

	public function getName(): string {
		return 'Add token cleanup job';
	}

	public function run(IOutput $output) {
		$this->jobList->add(TokenCleanupJob::class);
	}
}
