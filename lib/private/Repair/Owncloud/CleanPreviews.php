<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\Owncloud;

use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CleanPreviews implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
		private readonly IUserManager $userManager,
		private readonly IConfig $config,
	) {
	}

	public function getName(): string {
		return 'Add preview cleanup background jobs';
	}

	public function run(IOutput $output): void {
		if (!$this->config->getAppValue('core', 'previewsCleanedUp', false)) {
			$this->userManager->callForSeenUsers(function (IUser $user): void {
				$this->jobList->add(CleanPreviewsBackgroundJob::class, ['uid' => $user->getUID()]);
			});
			$this->config->setAppValue('core', 'previewsCleanedUp', '1');
		}
	}
}
