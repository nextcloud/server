<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\BackgroundJob;

use OC\Authentication\TwoFactorAuth\Manager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\IUser;
use OCP\IUserManager;

class CheckBackupCodes extends QueuedJob {

	/** @var Manager */
	private $twofactorManager;

	public function __construct(
		ITimeFactory $timeFactory,
		private IUserManager $userManager,
		private IJobList $jobList,
		Manager $twofactorManager,
		private IRegistry $registry,
	) {
		parent::__construct($timeFactory);
		$this->twofactorManager = $twofactorManager;
	}

	protected function run($argument) {
		$this->userManager->callForSeenUsers(function (IUser $user): void {
			if (!$user->isEnabled()) {
				return;
			}

			$providers = $this->registry->getProviderStates($user);
			$isTwoFactorAuthenticated = $this->twofactorManager->isTwoFactorAuthenticated($user);

			if ($isTwoFactorAuthenticated && isset($providers['backup_codes']) && $providers['backup_codes'] === false) {
				$this->jobList->add(RememberBackupCodesJob::class, ['uid' => $user->getUID()]);
			}
		});
	}
}
