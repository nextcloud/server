<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\NC21;

use OC\Core\BackgroundJobs\CheckForUserCertificates;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddCheckForUserCertificatesJob implements IRepairStep {
	public function __construct(
		private IConfig $config,
		protected IJobList $jobList,
	) {
	}

	public function getName() {
		return 'Queue a one-time job to check for user uploaded certificates';
	}

	private function shouldRun() {
		$versionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0.0');

		// was added to 21.0.0.2
		return version_compare($versionFromBeforeUpdate, '21.0.0.2', '<');
	}

	public function run(IOutput $output) {
		if ($this->shouldRun()) {
			$this->config->setAppValue('files_external', 'user_certificate_scan', 'not-run-yet');
			$this->jobList->add(CheckForUserCertificates::class);
		}
	}
}
