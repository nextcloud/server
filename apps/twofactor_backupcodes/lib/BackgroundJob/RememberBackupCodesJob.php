<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\IUserManager;
use OCP\Notification\IManager;

class RememberBackupCodesJob extends TimedJob {

	public function __construct(
		private IRegistry $registry,
		private IUserManager $userManager,
		ITimeFactory $timeFactory,
		private IManager $notificationManager,
		private IJobList $jobList,
	) {
		parent::__construct($timeFactory);
		$this->time = $timeFactory;

		$this->setInterval(60 * 60 * 24 * 14);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		$uid = $argument['uid'];
		$user = $this->userManager->get($uid);

		if ($user === null) {
			// We can't run with an invalid user
			$this->jobList->remove(self::class, $argument);
			return;
		}

		$providers = $this->registry->getProviderStates($user);
		$state2fa = array_reduce($providers, function (bool $carry, bool $state) {
			return $carry || $state;
		}, false);

		/*
		 * If no provider is active or if the backup codes are already generate
		 * we can remove the job
		 */
		if ($state2fa === false || (isset($providers['backup_codes']) && $providers['backup_codes'] === true)) {
			// Backup codes already generated lets remove this job
			$this->jobList->remove(self::class, $argument);
			return;
		}

		$date = new \DateTime();
		$date->setTimestamp($this->time->getTime());

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('twofactor_backupcodes')
			->setUser($user->getUID())
			->setObject('create', 'codes')
			->setSubject('create_backupcodes');
		$this->notificationManager->markProcessed($notification);

		if (!$user->isEnabled()) {
			// Don't recreate a notification for a user that can not read it
			$this->jobList->remove(self::class, $argument);
			return;
		}
		$notification->setDateTime($date);
		$this->notificationManager->notify($notification);
	}
}
