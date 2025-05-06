<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Async\ForkManager;
use OC\Config\Lexicon\CoreConfigLexicon;
use OC\Core\BackgroundJobs\AsyncProcessJob;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddAsyncProcessJob implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function getName() {
		return 'Setup AsyncProcess and queue job to periodically manage the feature';
	}

	public function run(IOutput $output) {
		$this->jobList->add(AsyncProcessJob::class);
	}
}
