<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair\Owncloud;

use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MoveAvatars implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
		private readonly IConfig $config,
		private IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Add move avatar background job';
	}

	#[\Override]
	public function run(IOutput $output): void {
		// only run once
		if ($this->appConfig->getValue('core', 'moveavatarsdone') === 'yes') {
			$output->info('Repair step already executed');
			return;
		}
		if (!$this->config->getSystemValueBool('enable_avatars', true)) {
			$output->info('Avatars are disabled');
		} else {
			$output->info('Add background job');
			$this->jobList->add(MoveAvatarsBackgroundJob::class);
			// if all were done, no need to redo the repair during next upgrade
			$this->appConfig->setValue('core', 'moveavatarsdone', 'yes');
		}
	}
}
