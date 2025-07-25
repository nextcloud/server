<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\RegisterRegenerateBirthdayCalendars;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RegenerateBirthdayCalendars implements IRepairStep {

	/**
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(
		private IJobList $jobList,
		private IConfig $config,
	) {
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Regenerating birthday calendars to use new icons and fix old birthday events without year';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		// only run once
		if ($this->config->getAppValue('dav', 'regeneratedBirthdayCalendarsForYearFix') === 'yes') {
			$output->info('Repair step already executed');
			return;
		}

		$output->info('Adding background jobs to regenerate birthday calendar');
		$this->jobList->add(RegisterRegenerateBirthdayCalendars::class);

		// if all were done, no need to redo the repair during next upgrade
		$this->config->setAppValue('dav', 'regeneratedBirthdayCalendarsForYearFix', 'yes');
	}
}
