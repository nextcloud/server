<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Listener;

use OCA\TwoFactorBackupCodes\BackgroundJob\RememberBackupCodesJob;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<TwoFactorProviderForUserRegistered> */
class ProviderEnabled implements IEventListener {

	public function __construct(
		private IRegistry $registry,
		private IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof TwoFactorProviderForUserRegistered)) {
			return;
		}

		$providers = $this->registry->getProviderStates($event->getUser());
		if (isset($providers['backup_codes']) && $providers['backup_codes'] === true) {
			// Backup codes already generated nothing to do here
			return;
		}

		$this->jobList->add(RememberBackupCodesJob::class, ['uid' => $event->getUser()->getUID()]);
	}
}
