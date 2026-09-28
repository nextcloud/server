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
	/** @var IJobList */
	private $jobList;

	/** @var IUserManager */
	private $userManager;

	/** @var IConfig */
	private $config;

	/**
	 * MoveAvatars constructor.
	 *
	 * @param IJobList $jobList
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 */
	public function __construct(IJobList $jobList,
		IUserManager $userManager,
		IConfig $config) {
		$this->jobList = $jobList;
		$this->userManager = $userManager;
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Add preview cleanup background jobs';
	}

	public function run(IOutput $output) {
		if (!$this->config->getAppValue('core', 'previewsCleanedUp', false)) {
			$this->userManager->callForSeenUsers(function (IUser $user) {
				$this->jobList->add(CleanPreviewsBackgroundJob::class, ['uid' => $user->getUID()]);
			});
			$this->config->setAppValue('core', 'previewsCleanedUp', '1');
		}
	}
}
